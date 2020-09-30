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
            if ($allowCheckout) {
                echo "Tüm proje yeniden alınıyor...\n";
                if ($rm = realpath(self::PATH_TEMP_PROJECTS)) {
                    system('rm -rf -- ' . escapeshellarg($rm), $retval);
                }
                if ($rm = realpath(self::PATH_PROJECTS)) {
                    system('rm -rf -- ' . escapeshellarg($rm), $retval);
                }
                sleep(2);
            }
        }


        if ($allowList) {
            if ($allowCheckout) {
                foreach ($allowList as $filePath) {
                    $path = explode("/", $filePath);
                    $endIndex = count($path) - 1;
                    $file = $path[$endIndex];
                    unset($path[$endIndex]);
                    $path = implode("/", $path);
                    if ($this->createDir(self::PATH_PROJECTS  . $path)) {
                        $fileSource = Curl::post("get-file", ['path' => $filePath], 'raw');

                        $this->putCoderProjects($filePath, $fileSource);

                        if ($this->createDir(self::PATH_TEMP_PROJECTS . $path)) {
                            $this->putTemp( $filePath, $fileSource);
                        }
                    }
                }
            }else{
                echo "\nYukarıdaki listelenen dosyalarda hali hazırda düzeltmeler var lütfen önce \"php coder push\" yapmayı dene.\n";
            }
        }
    }

}