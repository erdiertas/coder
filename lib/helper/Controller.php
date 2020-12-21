<?php


use yii\helpers\ArrayHelper;

class Controller
{
    const JSON_FILE_ALLOW_LIST = '/allow-list.json';
    const PATH_PROJECTS = __DIR__ . '/../../../CoderProjects';
    const PATH_TEMP_PROJECTS = __DIR__ . '/../temp/CoderProjects';

    public function getAllowList()
    {
        $allowList = Curl::post('allow-list');

        if ($allowList->success) {
            $this->putTemp(self::JSON_FILE_ALLOW_LIST, json_encode($allowList->list));
            return $allowList->list;
        } else {
            echo "Yetkisiz işlem, lütfen init komutu ile yetki alın.\n";
        }
        return false;
    }


    public function getKey()
    {
        return file_get_contents('../coder.pem');
    }

    public function putTemp($path, $data, $hideMessage = false)
    {
        $return = file_put_contents(self::PATH_TEMP_PROJECTS . $path, $data);
        if ($hideMessage === false) {
            if ($return) {
                echo "$path eklendi. \n";
            } else {
                echo "$path dosyası kaydedilemedi! \n";
            }
        }
        return $return;
    }

    public function readTempJson($path, $default = null)
    {
        if (file_exists(self::PATH_TEMP_PROJECTS . $path)) {
            $return = file_get_contents(self::PATH_TEMP_PROJECTS . $path);
            return json_decode($return, true);
        }
        return $default;
    }

    public function putCoderProjects($path, $data)
    {
        $return = file_put_contents(self::PATH_PROJECTS . $path, $data);
        if ($return) {
            echo "$path eklendi. \n";
        } else {
            echo "$path dosyası kaydedilemedi! \n";
        }
        return $return;
    }


    public static function getPath($path)
    {
        if (file_exists(__DIR__ . '/../' . $path)) {
            return realpath(__DIR__ . '/../' . $path);
        }
        return false;
    }

    public function createDir($path)
    {
        if (!file_exists($path)) {
            echo "$path oluşturuldu. \n";
            return mkdir($path, 0777, true);
        }
        return is_dir($path);
    }

    public function getCacheAllowList()
    {
        return json_decode(file_get_contents(self::PATH_TEMP_PROJECTS . self::JSON_FILE_ALLOW_LIST));
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

}