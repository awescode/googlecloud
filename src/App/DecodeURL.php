<?php

namespace Awescode\GoogleCloud\App;
include_once 'Jnt.php';

class DecodeURL extends Jnt
{

    //Файл настроек
    public $config;
    //Входящий адрес для парсинга
    public $encodeUrl;
    //Полный путь до превью
    public $thumb;
    public $extension;
    public $thumbNext;
    //Полный путь до оригинала
    public $image;

    //Метаинформация
    public $meta;
    public $hash;

    public function __construct($encodeUrl)
    {
        $this->init();
        $this->encodeUrl = $encodeUrl;
    }


    /**
     * @param $encodeUrl
     * @return bool
     */
    public function decodeUrl($encodeUrl = '')
    {
        if ($encodeUrl != '') {
            $this->encodeUrl = $encodeUrl;
        } else {
            $encodeUrl = $this->encodeUrl;
        }

        $fileInfo = new \SplFileInfo($encodeUrl);
        $fileName = $fileInfo->getBasename(".{$fileInfo->getExtension()}");

        $this->extension = $fileInfo->getExtension();

        if (!$this->setMeta($fileName)) {
            return false;
        }
        //Полный путь до превью
        $this->thumb = $this->thumbNext = $this->assemblyUrl([
            $this->config['thumb_folder'],
            $fileInfo->getPath(),
            $fileInfo->getBasename()
        ]);
        //Полный путь до оригинала
        $this->image = $this->assemblyUrl([
            $fileInfo->getPath(),
            $this->meta['imageName'] . '.' . $this->getExtId($this->meta['getExtId'], true)
        ]);
        return true;
    }

    public function setMeta($fileName) {

        $parse_result = $this->parseUrl($fileName);

        if (!$parse_result) {
            $this->meta = false;
            return false;
        }

        $this->hash = $parse_result['hash'];
        $this->meta = $parse_result['meta'];
        return true;
    }

    public function validate()
    {
        return ($this->meta && ($this->hash == $this->getHashObj($this->meta)));
    }

    /**
     * @param $object
     * @return bool|float
     */
    public function isAllowedSize($object)
    {
        $info = $object->info();
        if (isset($info['size'])) {
            $size_in_mb = $info['size'] / 1024 / 1024;
            if ($size_in_mb > $this->config['max_size']) {
                return round($size_in_mb, 2);
            }
        }
        return false;
    }

    //Следующее изображение
    public function getThumbNext() {

        $thumb = $this->thumbNext;

        $parse_result = $this->parseUrl($thumb);

        $fileInfo = new \SplFileInfo($thumb);
        $filePath = $fileInfo->getPath();
        $fileExt = $fileInfo->getExtension();

        $parse_result['meta']['modify'] = str_replace("." . $fileExt , "", $parse_result['meta']['modify']);

        $mArr = explode('--', $parse_result['meta']['modify']);

        // Change array for the next URL
        $op1 = array_shift($mArr);
        array_push($mArr, $op1);

        // Join to string from shifted array
        $parse_result['meta']['modify'] = implode('--', $mArr);

        // Save for the next iteration
        $this->thumbNext = $this->buildUrl($filePath, $parse_result['clear_slug'], $parse_result['meta']['imageName'], $parse_result['meta']['getExtId'], $parse_result['meta']['modify'], $fileExt);

        return $thumb;
    }

}
