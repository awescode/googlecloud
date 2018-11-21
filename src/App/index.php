<?php

namespace Awescode\GoogleCloud\App;

require 'vendor/autoload.php';
require_once 'DecodeURL.php';
require_once 'helper.php';

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

// The key is not valid
if (!$gh->validate()) {
    header('HTTP/1.0 403 Forbidden');
    exit;
}

// The image already cropped, return 301 redirect to thumb
if ($thumb->exists()) {
    $gh->redirect($gh->config['cdn-static'] . '/' . $gh->thumb);
}

// The image was not found, return 404
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

// Check input options, if the options are not exist, return original
$options = ($gh->meta['modify'] && $gh->meta['modify'] !== '--') ? explode('--', $gh->meta['modify']) : false;
if (!$options || count($options) < 1) {
    $gh->redirect($gh->config['cdn-static'] . '/' . $gh->image);
}

// Getting Magic link for croping
try {
    $magicUrl = CloudStorageTools::getImageServingUrl($image->gcsUri());
} catch (CloudStorageException $e) {
    syslog(LOG_ERR, 'There was an exception creating the Image Serving URL, details ' . $e->getMessage());
    header('Content-Type: ' . $gh->config['error_image_type']);
    header('Error: ' . $gh->config['error_image_not_valid']);
    echo base64_decode($gh->config['error_image']);
    exit;
}


// Thumb generation
foreach ($options as $option) {
    $name = $gh->getThumbNext();

    $cropMagicLink = $magicUrl . '=' . $option . '-v' . time();

    $imageContent = grab_image($cropMagicLink);

    if ($imageContent === false) {
        syslog(LOG_ERR, 'There was an exception creating - The modify variables is not correct. ');
        header('Content-Type: ' . $gh->config['error_image_type']);
        header('Error: The modify variables is not correct.');
        echo base64_decode($gh->config['error_image']);
        exit;
    }

    $bucket->upload(
        $imageContent,
        [
            'name' => $name,
            'media' => true,
            'publicRead' => true,
            'metadata' => [
                'cacheControl' => 'public, max-age=2592000',
                'contentType' => $gh->getContentType($gh->extension)
            ]
        ]
    );
}

// Removing magic link from google
CloudStorageTools::deleteImageServingUrl($image->gcsUri());

// Send redirect to image to browser
$gh->redirect($gh->config['cdn-static'] . '/' . $gh->thumb);
