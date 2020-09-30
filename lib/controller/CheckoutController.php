<?php


class CheckoutController extends Controller
{

    public function actionIndex($params)
    {
        $this->createDir(self::PATH_TEMP_PROJECTS);

        $allowList = $this->getAllowList();

        $allowCheckout = true;
        if ($allowList) {
            foreach ($allowList as $file) {
                $old_version = self::PATH_TEMP_PROJECTS . $file;
                $new_version = self::PATH_PROJECTS . $file;
                if (file_exists($new_version)) {
                    if (@hash_file('md5', $old_version) != @hash_file('md5', $new_version)) {
                        echo "$file değiştirilmiş.\n";
                        $allowCheckout = false;
                    }
                }
            }
        }

        if ($allowList ) {
            if ($allowCheckout) {
                foreach (self::scanDir(realpath(self::PATH_PROJECTS)) as $filePath) {
                    list(,$filePath) = explode("CoderProjects", $filePath, 2);
                    if (array_search($filePath, $allowList) === false) {
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
                    $path = explode("/", $filePath);
                    $endIndex = count($path) - 1;
                    $file = $path[$endIndex];
                    unset($path[$endIndex]);
                    if (!file_exists(self::PATH_PROJECTS  . $filePath)) {
                        $path = implode("/", $path);
                        if ($this->createDir(self::PATH_PROJECTS  . $path)) {
                            $fileSource = Curl::post("get-file", ['path' => $filePath], 'raw');
                            $this->putCoderProjects($filePath, $fileSource);
                            if ($this->createDir(self::PATH_TEMP_PROJECTS . $path)) {
                                $this->putTemp( $filePath, $fileSource);
                            }
                        }
                    }
                }
                echo "\nProje içindeki yetkili olduğunuz dosyalar varsa, içeri alındı. İyi çalışmalar. :)\n\n";
            }else{
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

}