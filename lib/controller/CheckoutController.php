<?php


class CheckoutController extends Controller
{

    public function actionIndex($params)
    {
        $this->createDir($this::getPath('temp') . '/projects');

        $allowList = $this->getAllowList();

        $allowCheckout = true;
        foreach ($allowList as $file) {
            $old_version = $this::getPath('/temp/projects' . $file);
            $new_version = $this::getPath('../projects' . $file);
            if (@hash_file('md5', $old_version) != @hash_file('md5', $new_version)) {
                echo "$file değiştirilmiş.\n";
                $allowCheckout = false;
            }
        }

        if ($allowCheckout) {
            echo "Tüm proje yeniden alınıyor...\n";
            if ($rm = $this::getPath('/temp/projects')) {
                system('rm -rf -- ' . escapeshellarg($rm), $retval);
            }
            if ($rm = $this::getPath('../projects')) {
                system('rm -rf -- ' . escapeshellarg($rm), $retval);
            }
            sleep(2);
        }

        if ($allowList) {
            if ($allowCheckout) {
                foreach ($allowList as $filePath) {
                    $path = explode("/", $filePath);
                    $endIndex = count($path) - 1;
                    $file = $path[$endIndex];
                    unset($path[$endIndex]);
                    $path = implode("/", $path);
                    if ($this->createDir($this::getPath("/../") . "/projects" . $path)) {
                        $fileSource = Curl::post("get-file", ['path' => $filePath], 'raw');

                        $this->putProjects($filePath, $fileSource);

                        if ($this->createDir($this::getPath('temp') . "/projects/" . $path)) {
                            $this->putTemp('/projects' . $filePath, $fileSource);
                        }
                    }
                }
            }else{
                echo "\nYukarıdaki listelenen dosyalarda hali hazırda düzeltmeler var lütfen önce \"php coder push\" yapmayı dene.\n";
            }
        }
    }

}