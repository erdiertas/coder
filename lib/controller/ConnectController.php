<?php


class ConnectController extends Controller
{
    public $token;

    public function getMainPath()
    {
        $mainPath = realpath(__DIR__ . '/../../../');
        return $mainPath;
    }

    public function getCoderProjectsPath()
    {
        $mainPath = $this->getMainPath();
        $coderPath = $mainPath . '/CoderProjects';
        if (!file_exists($coderPath) && !mkdir($coderPath) && !is_dir($coderPath)) {
            echo "$coderPath klasörü oluşturulamıyor! Lütfen oluştur ve 0777 yazma izni tanımla.\n";
        }
        return $coderPath;
    }

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


    public function actionIndex($params)
    {
        echo "\nCODER 1.0 \n\n";
        echo "Hoş Geldiniz! \n\n";

        $tempFolder = __DIR__ . '/../temp';
        $tokenFile = $tempFolder . '/.token';

        login:
        if (!file_exists($tokenFile)) {
            echo "Username: ";
            $handle = fopen("php://stdin", "r");
            $username = fgets($handle);
            fclose($handle);
            echo "Password: ";
            system('stty -echo');
            $password = trim(fgets(STDIN));
            system('stty echo');
            echo "\n\n";
            $login = Curl::post("login", [
                'username' => $username,
                'password' => $password
            ]);

            if ($login['status']) {
                file_put_contents($tokenFile, $login['username'] . ':' . $login['auth_key']);
            } else {
                echo "\n\n";
                echo "Giriş yapılamadı!\n\n";
                exit();
            }
        }

        $token = file_get_contents($tokenFile);
        $this->token = $token;

        echo "Proje hazırlanıyor... Lütfen bekleyin... \n\n";
        $coderPath = $this->getCoderProjectsPath();
        $coderPathLen = strlen($coderPath) + 1;


        $model = Curl::post("allow-list", ['token' => $token]);
        if (!$model['status']) {
            unlink($tokenFile);
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
                    'token' => $token,
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
                        'token' => $token,
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
                'token' => $token,
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
        $coderPath = $this->getCoderProjectsPath();
        echo "Dosya klonlanıyor: $path \n";
//        $fileRaw = file_get_contents("http://local-evimdehobi.usecomer.com/coder/api/clone-file?path=" . urlencode($path));

        $fileRaw = Curl::post("clone-file", [
            'token' => $this->token,
            'path' => $path
        ], 'raw');

        if ($fileRaw === "!!__CODER_NO_AUTHORIZATION__!!") {
            echo "! Bu dosyayı okuma yetkiniz bulunmuyor: $path \n";
        } else {
            $pathInfo = pathinfo($path);
            $dirname = $coderPath . '/' . $pathInfo['dirname'];
            if (!file_exists($dirname) && !mkdir($concurrentDirectory = $dirname, 0777, true) && !is_dir($concurrentDirectory)) {
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