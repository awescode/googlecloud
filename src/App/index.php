<?php

namespace Awescode\GoogleCloud\App;

require 'vendor/autoload.php';
require_once 'DecodeURL.php';

use Google\Cloud\Storage\StorageClient;
use google\appengine\api\cloud_storage\CloudStorageTools;
use google\appengine\api\cloud_storage\CloudStorageException;

//Init libs
$gh = new DecodeURL($_SERVER['REQUEST_URI']);

// If request is okay
if (!$gh->decodeUrl()) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

$storage = new StorageClient();

//Init objects
$bucket = $storage->bucket($gh->config['bucket']);
$image = $bucket->object($gh->image);
$thumb = $bucket->object($gh->thumb);

//Processing

//Запрос не прошел провреку
if (!$gh->validate()) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

//Превью есть, отдаем 301 редирект
if ($thumb->exists()) {
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: {$gh->config['cdn-static']}/{$gh->thumb}");
    exit;
}

//Такого изображения нет, отдаем 404
if (!$image->exists()) {
    header("HTTP/1.0 404 Not Found");
    exit;
}

// Check the size of the image
if ($size = $gh->isAllowedSize($image)) {
    syslog(LOG_INFO, 'The source image (' . $gh->image . ') is too big.');
    header('Content-Type: ' . $gh->config['error_image_type']);
    header('Error: ' . str_replace(":size", $size, $gh->config['error_image_to_big']));
    echo base64_decode($gh->config['error_image']);
    exit;
}

//Проверка переданных опций, если опции не заданы, отдаем оригинал
$options = ($gh->meta['modify'] && $gh->meta['modify'] !== '--') ? explode('--', $gh->meta['modify']) : false;
if (!$options || count($options) < 1) {
    header('HTTP/1.1 301 Moved Permanently');
    header("Location: {$gh->config['cdn-static']}/{$gh->image}");
    exit;
}

//Генерация превью, отдаем в браузер
foreach ($options as $option) {
    try {
        $magicUrl = CloudStorageTools::getImageServingUrl($image->gcsUri());
        $bucket->upload(
            file_get_contents($magicUrl . '=' . $option),
            ['name' => $gh->getThumbNext()]
        );
    } catch (CloudStorageException $e) {
        syslog(LOG_ERR, 'There was an exception creating the Image Serving URL, details ' . $e->getMessage());
        header('Content-Type: ' . $gh->config['error_image_type']);
        header('Error: ' . $gh->config['error_image_not_valid']);
        echo base64_decode($gh->config['error_image']);
        exit;
    }
}

//$thumb->reload();

//Отдаем картику в браузер
header("Content-Type: " . $thumb->info()['contentType']);
header('Content-Length: ' . $thumb->info()['size']);
$stream = $thumb->downloadAsStream();
echo $stream->getContents();
exit;
