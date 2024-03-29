<?php


class ConnectController extends Controller
{

    public function scanDir($source_dir, $ignores = [], $directory_depth = 0, $hidden = FALSE, $firstPath = null)
    {
        if (!$firstPath) {
            $firstPath = $source_dir . '/';
        }
        if ($fp = @opendir($source_dir)) {
            $filedata = array();
            $new_depth = $directory_depth - 1;
            $source_dir = rtrim($source_dir, '/') . '/';

            while (FALSE !== ($file = readdir($fp))) {
                if (!trim($file, '.') or ($hidden == FALSE && $file[0] === '.')) {
                    continue;
                }

                if (($directory_depth < 1 or $new_depth > 0) && @is_dir($source_dir . $file)) {
                    if (in_array($source_dir . $file, $ignores)) {
                        continue;
                    }
                    $subFiledata = $this->scanDir($source_dir . $file . '/', $ignores, $new_depth, $hidden, $firstPath . $file . '/');
                    $filedata = array_merge($filedata, $subFiledata);
//                    if (!$subFiledata) {
//                        @rmdir($source_dir . $file . '/');
//                    }
                } else {
                    $filedata[] = $firstPath . $file;
                }
            }

            closedir($fp);
            return $filedata;
        }
        return [];
    }

    public function prompt_silent($prompt = "Enter Password:")
    {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents(
                $vbscript, 'wscript.echo(InputBox("'
                . addslashes($prompt)
                . '", "", "password here"))');
            $command = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
            return $password;
        } else {
            $command = "/usr/bin/env bash -c 'echo OK'";
            if (rtrim(shell_exec($command)) !== 'OK') {
                trigger_error("Can't invoke bash");
                return;
            }
            $command = "/usr/bin/env bash -c 'read -s -p \""
                . addslashes($prompt)
                . "\" mypassword && echo \$mypassword'";
            $password = rtrim(shell_exec($command));
            echo "\n";
            return $password;
        }
    }


    public function actionIndex($params)
    {
       echo $this->showWelcomeMessage();

        login:
        $this->login();

        echo "Proje hazırlanıyor... Lütfen bekleyin... \n\n";
        $coderPath = $this::coderProjectsPath();
        $coderPathLen = strlen($coderPath) + 1;

        $model = Curl::post("allow-list", ['token' => $this->token]);
        if (!$model['status']) {
            $this->tokenRemove();
            goto login;
        }

        $allowList = [];
        $ignoreList = [];
        foreach ($model['models'] as $model) {
            $path = $coderPath . '/' . $model['path'];
            $allowList[$path] = $model['path'];
            if ($model['allow_read'] && !file_exists($path)) {
                $this->fileClone($model['path']);
            }
            if ($model['ignore']) {
                $ignoreList[] = rtrim($path, '/');
            }
        }

        $filesChangedTimes = [];
        foreach ($this->scanDir($coderPath, $ignoreList) as $item) {
            if (!isset($allowList[$item])) {
                unlink($item);
            } else {
                $filesChangedTimes[$item] = filemtime($item);
            }
        }

        echo "Yeni güncellemeler kontrol ediliyor...\n\n";

        $hashList = [];
        foreach ($filesChangedTimes as $filePath => $filemtime) {
            $serverPath = substr($filePath, $coderPathLen);
            $hashList[$serverPath] = md5_file($filePath);
        }

        $checkUpdated = Curl::post("check-updated", [
            'token' => $this->token,
            'files' => $hashList,
        ]);
        foreach ($checkUpdated as $serverPath) {
            $item = $coderPath . '/' . $serverPath;
            echo "Değiştirilmiş: $serverPath \n";
            unlink($item);
            $this->fileClone($serverPath);
            $filesChangedTimes[$item] = filemtime($item);
        }
        if ($checkUpdated) {
            echo "\n";
        }

        echo "Proje hazırlandı, şimdi rahatlıkla geliştirmelerinizi yapabilirsiniz.\nBug'sız kodlar dilerim... :) \n\n";

        $firstList = [];
        check:
        $lastList = self::scanDir($coderPath, $ignoreList);
        if (!$firstList) {
            $firstList = $lastList;
        }
        foreach ($lastList as $filePath) {
            $serverPath = substr($filePath, $coderPathLen);

//            print_r($allowList[$filePath]);

            if (!isset($allowList[$filePath])) {
                echo "Yeni dosya ekleniyor: $serverPath \n";
                $content = file_get_contents($filePath);
                $add = Curl::post("add-file", [
                    'token' => $this->token,
                    'path' => $serverPath,
                    'content' => $content,
                ]);
                if ($add['status']) {
                    $allowPathList[$serverPath] = 1;
                    echo "Yeni dosya eklendi: $serverPath \n";
                    $allowList[$filePath] = $serverPath;
                } else {
                    echo "! Yeni dosya eklenemedi: $serverPath \n";
                }
                echo "\n";
            }

            $newTime = filemtime($filePath);
            if (isset($filesChangedTimes[$filePath])) {
                if ($filesChangedTimes[$filePath] < $newTime) {
                    echo "Dosya gönderiliyor: $serverPath \n";
                    $content = file_get_contents($filePath);
                    $push = Curl::post("push-file", [
                        'token' => $this->token,
                        'path' => $serverPath,
                        'content' => $content
                    ]);
                    if ($push['status']) {
                        echo "Dosya gönderildi: $serverPath \n";
                    } else {
                        echo $push['message'] . "\n";
                    }
                    echo "\n";
                }
            }
            $filesChangedTimes[$filePath] = $newTime;
        }
        /**
         * Silinenleri bul
         */
        foreach (array_diff($firstList, $lastList) as $filePath) {
            $serverPath = substr($filePath, $coderPathLen);
            echo "Kaldırılıyor: $serverPath \n";
            $remove = Curl::post("remove-file", [
                'token' => $this->token,
                'path' => $serverPath
            ]);
            if ($remove['status']) {
                unset($allowList[$filePath]);
                echo "Kaldırıldı: $serverPath \n";
            } else {
                echo "Kaldırılamadı: $serverPath \n";
            }
            echo "\n";
        }
        $firstList = $lastList;
        sleep(3);
        goto check;

    }

    public function fileClone($path)
    {
        $coderPath = $this::coderProjectsPath();
        $fileRaw = Curl::post("clone-file", [
            'token' => $this->token,
            'path' => $path
        ], 'raw');
        if (strlen($fileRaw) === 0) {
            return;
        }
        echo "Dosya klonlanıyor: $path \n";

        if ($fileRaw === "!!__CODER_NO_AUTHORIZATION__!!") {
            echo "! Bu dosyayı okuma yetkiniz bulunmuyor: $path \n";
        } else {
            $pathInfo = pathinfo($path);
            $dirname = $coderPath . '/' . $pathInfo['dirname'];
            if (!file_exists($dirname) && !mkdir($concurrentDirectory = $dirname, 0777, true)
                && !is_dir($concurrentDirectory)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $concurrentDirectory));
            }
            $file = $coderPath . '/' . $path;
            if (!empty($fileRaw)) {
                if (!file_put_contents($file, $fileRaw)) {
                    echo "! Bu dosya klonlanamadı: $path \n";
                }
            }
        }
    }


}