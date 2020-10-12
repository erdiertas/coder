<?php


class CheckoutController extends Controller
{

    public function actionIndex($params)
    {

        $this->createDir(self::PATH_TEMP_PROJECTS);

        $allowList = $this->getAllowList();
        $allowListPaths = [];
        $allowCheckout = true;
        $hasNewVersion = [];
        if ($allowList) {
            foreach ($allowList as $file) {
                $allowPathVersion = $file->version;
                $file = $file->path;
                $allowListPaths[] = $file;
                $old_version = self::PATH_TEMP_PROJECTS . $file;
                $new_version = self::PATH_PROJECTS . $file;
                if (file_exists($new_version)) {
                    $new_version_md5 = @md5_file($new_version);
                    if (@md5_file($old_version) != $new_version_md5) {
                        echo "$file değiştirilmiş.\n";
                        $allowCheckout = false;
                    }
                    if ($allowPathVersion != $new_version_md5) {
                        $hasNewVersion[] = $new_version;
                    }
                }
            }
        }

        if ($allowList) {
            if ($allowCheckout) {
                foreach ($hasNewVersion as $value) {
                    unlink($value);
                }
                foreach (self::scanDir(realpath(self::PATH_PROJECTS)) as $filePath) {
                    list(, $filePath) = explode("CoderProjects", $filePath, 2);
                    if (in_array($filePath, $allowListPaths) === false) {
                        $rmFile = realpath(self::PATH_PROJECTS . $filePath);
                        if (file_exists($rmFile)) {
                            unlink($rmFile);
                        }
                    }
                }

                /**
                 * Boşları temizle
                 */
                self::scanDir(realpath(self::PATH_PROJECTS));

                foreach ($allowList as $filePath) {
                    $filePath = $filePath->path;
                    $path = explode("/", $filePath);
                    $endIndex = count($path) - 1;
                    $file = $path[$endIndex];
                    unset($path[$endIndex]);
                    if (!file_exists(self::PATH_PROJECTS . $filePath)) {
                        $path = implode("/", $path);
                        if ($this->createDir(self::PATH_PROJECTS . $path)) {
                            $cmd = $params[0] . ' co/getFile ' . $filePath . ' ' . $path;
                            system('cd ' . $_SERVER["PWD"] . ' && ' . $_SERVER["_"] . ' ' . $cmd . ' > /dev/null &'); // . ' > /dev/null &'
                        }
                    }
                }
                echo "\nProje içindeki yetkili olduğunuz dosyalar varsa, içeri alındı. İyi çalışmalar. :)\n\n";
            } else {
                echo "\nYukarıdaki listelenen dosyalarda hali hazırda düzeltmeler var lütfen önce \"php coder push\" yapmayı dene.\n";
            }
        } else {
            if ($allowCheckout) {
                if ($rm = realpath(self::PATH_TEMP_PROJECTS)) {
                    system('rm -rf -- ' . escapeshellarg($rm), $retval);
                }
                if ($rm = realpath(self::PATH_PROJECTS)) {
                    system('rm -rf -- ' . escapeshellarg($rm), $retval);
                }
                sleep(2);
            }

        }
    }

    public function actionGetFile($params)
    {
        $filePath = $params[2];
        $path = $params[3];
        $fileSource = Curl::post("get-file", [
            'path' => $filePath,
            'tempHash' => file_exists(self::PATH_PROJECTS . $filePath) ? md5_file(self::PATH_TEMP_PROJECTS . $filePath) : 0
        ], 'raw');
        if (!empty($fileSource)) {
            $this->putCoderProjects($filePath, $fileSource);
            if ($this->createDir(self::PATH_TEMP_PROJECTS . $path)) {
                $this->putTemp($filePath, $fileSource);
            }
        }
    }

}