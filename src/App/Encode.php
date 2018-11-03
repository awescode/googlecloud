<?php

namespace Awescode\GoogleCloud\App;


class Encode extends Jnt
{

    public $path;
    public $options;
    public $config;

    /**
     * encode constructor.
     *
     * @param $path
     * @param $options
     * @param $config
     */
    public function __construct($path, $options, $config)
    {
        $this->path = $path;
        $this->options = (object)$options;
        $this->config = $config;
    }

    /**
     * Получение ключа, по которому можно однозначно определить HTML для изображениия и 2-х версии
     *
     * @param string $pref
     * @return string
     */
    public function getKey($pref = 'img') {
        return $pref. $this->path . implode('_', (array)$this->options) . $this->config['secret_key'];
    }

    /**
     * Получить URI оригинальной картинки
     *
     * @return mixed
     */
    public function getOriginal() {
        return $this->path;
    }

    //{$folder}/{$slug}_{$imageHash}-{$secretHash}-{$fileExtId}_{$modify}.{$fileExt}"
    /**
     * Получить URI до картинки на GCS
     *
     * @param bool $p
     * @return string
     */
    public function getPath($p = false, $set = []) {
        $fileInfo = $this->getFileInfo($this->path);

        //Отдаем оригинальную картинку
        if (isset($this->options->original) && $this->options->original) {
            return $this->path;
        }
        //Генерируем ссылку на кропнутую картинку
        $opt = $this->multOpt($this->options->modify, $p, $set);
        $slug = $this->options->slug ?? $this->options->alt ?? $this->options->title ?? '';
        $slug = $this->strClear($slug);
        $ext = $this->options->ext ?? $fileInfo->ext;

        //Хеш картинки
        $hash = implode("_", [
            $fileInfo->name,
            $this->getHashObj([
                $this->config['secret_key'],
                $slug,
                $fileInfo->name,
                $this->getExtId($fileInfo->ext),
                $opt
            ]),
            $this->getExtId($fileInfo->ext)
        ]);

        return $this->assemblyUrl([
            $fileInfo->path,
            "{$slug}_{$hash}_{$opt}.{$ext}"
        ]);
    }

    /**
     * @return bool
     */
    public function getAlt() {
        $alt = $this->options->alt ?? $this->options->slug ?? $this->options->title ?? false;
        return ($alt) ? $alt : $this->getFileInfo($this->path)->name;
    }

    /**
     * @return bool
     */
    public function isOriginal() {
        return !($this->options->modify ?? false);
        //return !($this->options->modify ?? $this->options->ext ?? false);
    }

    //======================================================================
    // Support function
    //======================================================================

    //Получить хеш файла TODO: только для тестов
    public function testSecretHash($p = false) {
        if (!isset($this->options->modify)) return '';

        $fileInfo = $this->getFileInfo($this->path);

        $slug = $this->options->slug ?? $this->options->alt ?? $this->options->title ?? '';
        $slug = $this->strClear($slug);//TODO: вынести отдельно

        $opt = $this->multOpt($this->options->modify, $p);
        return $this->getHashObj([
            $this->config['secret_key'],
            $slug,
            $fileInfo->name,
            $this->getExtId($fileInfo->ext),
            $opt
        ]);
    }
}
