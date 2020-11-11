<?php


class FileHelper
{

    public static function pathToCoderPath($path)
    {
        return str_replace(realpath(Controller::PATH_PROJECTS), "", $path);
    }
}