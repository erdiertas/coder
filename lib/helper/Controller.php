<?php


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

    public function putTemp($path, $data)
    {
        $return = file_put_contents(self::PATH_TEMP_PROJECTS . $path, $data);
        if ($return) {
            echo "$path eklendi. \n";
        } else {
            echo "$path dosyası kaydedilemedi! \n";
        }
        return $return;
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
        return json_decode(file_get_contents($this::getPath('temp' . self::JSON_FILE_ALLOW_LIST)));
    }
}