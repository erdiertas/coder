<?php


use yii\helpers\ArrayHelper;

class Controller
{
    public $token;

    public static function getPath($path)
    {
        if (file_exists(__DIR__ . '/../' . $path)) {
            return realpath(__DIR__ . '/../' . $path);
        }
        return false;
    }

    public static function getMainPath()
    {
        $mainPath = realpath(__DIR__ . '/../../../');
        return $mainPath;
    }

    public static function getCoderPath()
    {
        $mainPath = realpath(__DIR__ . '/../../');
        return $mainPath;
    }

    public static function getTempPath()
    {
        return static::getCoderPath() . '/temp';
    }

    public static function coderProjectsPath()
    {
        $mainPath = static::getMainPath();
        $coderPath = $mainPath . '/CoderProjects';
        if (!file_exists($coderPath) && !mkdir($coderPath) && !is_dir($coderPath)) {
            echo "$coderPath klasörü oluşturulamıyor! Lütfen oluştur ve 0777 yazma izni tanımla.\n";
        }
        return $coderPath;
    }


    public static function recursiveRemove($path)
    {
        if (is_dir($path)) {
            foreach (scandir($path) as $entry) {
                if (!in_array($entry, ['.', '..'], true)) {
                    self::recursiveRemove($path . DIRECTORY_SEPARATOR . $entry);
                }
            }
            rmdir($path);
        } else {
            unlink($path);
        }
    }

    public function scanDir($source_dir, $directory_depth = 0, $hidden = FALSE, $firstPath = null)
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
                    $subFiledata = $this->scanDir($source_dir . $file . '/', $new_depth, $hidden, $firstPath . $file . '/');
                    $filedata = array_merge($filedata, $subFiledata);
                    if (!$subFiledata) {
                        @rmdir($source_dir . $file . '/');
                    }
                } else {
                    $filedata[] = $firstPath . $file;
                }
            }

            closedir($fp);
            return $filedata;
        }
        return [];
    }

    public function showWelcomeMessage()
    {
        return "\nCODER v1.0.1 \n\n"
            . "Hoş Geldiniz! \n\n";
    }

    public function login()
    {
        $tokenFile = self::getTempPath() . '/.token';
        if (!file_exists($tokenFile)) {
            Controller::recursiveRemove(Controller::coderProjectsPath());
            Controller::coderProjectsPath();
            echo "Username: ";
            $handle = fopen("php://stdin", "r");
            $username = fgets($handle);
            fclose($handle);
            echo "Password: ";
            $handle2 = fopen("php://stdin", "r");
            $password = fgets($handle2);
            fclose($handle2);
            $password = trim($password);
//            system('stty -echo');
//            $password = $this->prompt_silent();
//            system('stty echo');
            echo "\n\n";

            $login = Curl::post("login", [
                'username' => $username,
                'password' => $password
            ]);

            if ($login['status']) {
                file_put_contents($tokenFile, $login['username'] . ':' . $login['auth_key']);
            } else {
                Controller::recursiveRemove(Controller::coderProjectsPath());
                Controller::coderProjectsPath();
                echo "\n\n";
                echo "Giriş yapılamadı!\n\n";
                exit();
            }
        }

        $token = file_get_contents($tokenFile);
        $this->token = $token;

        return [
            'tokenFile' => $tokenFile,
        ];
    }

    public function tokenRemove()
    {
        $tokenFile = self::getTempPath() . '/.token';
        unlink($tokenFile);
    }
}