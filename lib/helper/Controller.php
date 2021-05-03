<?php


use yii\helpers\ArrayHelper;

class Controller
{

    public static function getPath($path)
    {
        if (file_exists(__DIR__ . '/../' . $path)) {
            return realpath(__DIR__ . '/../' . $path);
        }
        return false;
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