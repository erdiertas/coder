<?php


class ArrayHelper
{
    /**
     * @param $array
     * @param $key
     * @return mixed|null
     */
    public static function getValue($array, $key)
    {
        if (isset($array[$key])) {
            return $array[$key];
        }
        return null;
    }
}