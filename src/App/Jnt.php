<?php

namespace Awescode\GoogleCloud\App;

use URLify;

class Jnt
{
    public $config;

    function init() {
        $this->config = (include 'config/googlecloud.php');
    }

    /**
     * Получить хеш строки
     *
     * @param $str
     * @param int $len
     * @return string
     */
    public function getHash($str, $len = 8)
    {
        $hash = md5($str);
        return mb_substr($hash, 0, $len, 'UTF-8');
    }

    /**
     * Получение информации о файле (init)
     *
     * @param $path
     * @return object
     */
    public static function getFileInfo($path) {
        $fileInfo = new \SplFileInfo($path);

        $filePath = $fileInfo->getPath();
        $fileExt = $fileInfo->getExtension();
        $fileName = $fileInfo->getBasename(".{$fileExt}");
        return (object) [
            'path' => $filePath,
            'name' => $fileName,
            'ext' => $fileExt
        ];
    }

    /**
     * Секретный ключ
     *
     * @return mixed
     */
    public function getSecretKey()
    {
        return $this->config['secret_key'];
    }

    /**
     * Получаем расширение
     *
     * @param $ext
     * @param bool $reverse
     * @return bool
     */
    public function getExtId($ext, $reverse = false) {
        $conf = (!$reverse) ? $this->config['ext_mapping'] : array_flip($this->config['ext_mapping']);
        return (isset($conf[$ext])) ? $conf[$ext] : false;
    }

    /**
     * Парсим строку и умножаем на $m параметы отвечающие за размеры
     *
     * @param $opt
     * @param $m
     * @return mixed
     */
    public static function multOpt($opt, $m = false, $set = []) {
        if ($set != []) {
            $modify = [];
            foreach($set as $item) {
                $modify[] = $item->modify;
            }
            return implode("--", $modify);
        } else {
            if ($m !== false) {
                preg_match_all("/([w|h|s]\d{1,})/", $opt, $matches);
                $replace = [];
                $search = [];
                foreach ($matches as $match) {
                    foreach ($match as $item) {
                        $val = (int)substr($item, 1) * abs($m);
                        $replace[] = $item[0] . $val;
                        $search[] = $item;
                    }

                }
                if ($m < 0) {
                    return $opt . '--' . str_replace($search, $replace, $opt);
                }
                return str_replace($search, $replace, $opt) . '--' . $opt;
            }
        }
        return $opt;
    }

    /**
     * Безопасно собрать URL
     *
     * @param $array
     * @return string
     */
    public function assemblyUrl($array)
    {
        $clearArray = [];
        foreach ($array as $item) {
            if ($item) {
                $clearArray[] = trim($item, '/');
            }
        }
        return implode('/', $clearArray);
    }

    /**
     * Получить хеш объекта
     *
     * @param $obj
     * @return string
     */
    public function getHashObj($obj)
    {
        if (!$obj) return '';
        return $this->getHash(implode("", $obj));
    }

    /**
     * Получить строку с валидными символами для генерации URL
     *
     * @param $str
     * @return null|string|string[]
     */
    public function strClear($str) {
        URLify::remove_words([]);
        $str = str_replace('_', '-', $str);
        $test = preg_replace('~\.~', '', URLify::filter($str, $this->config['slug_length'], $this->getLocally(), true));
        return $test;
    }

    /**
     * Получить локаль
     *
     * @return mixed
     */
    public function getLocally()
    {
        return (isset($this->locally)) ? $this->locally : $this->config['locally'];
    }
}
