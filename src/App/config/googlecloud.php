<?php

return [

    'debug' => true,
    'cache' => true,

    'cdn-dynamic' => 'https://img.lagerbox.com',
    'cdn-static' => 'https://cdn.lagerbox.com',
    'bucket' => 'cdn.lagerbox.com',
    'secret_key' => 'c463bcdadf48765b205ca9e8fcfb66c3',
    'ext_mapping' => [
        'jpg' => 1,
        'png' => 2,
        'git' => 3,
        'webp' => 4,
        'jpeg' => 5
    ],
    'available_extensions' => ['jpg', 'png', 'gif', 'webp', 'jpeg'],
    'thumb_folder' => 'thumb',
    'slug_length' => 60,
    'max_size' => 15, // In megabytes
    'locally' => 'de',
    'storage' => 'gcs',
    'is_not_image' => '<!-- google cloud: :path - is not an image -->',
    'no_image' => '<!-- google cloud: no image -->',

    'error_image' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVQ',
    'error_image_type' > 'image/png',

    'error_image_to_big' => 'Google Cloud: sorry, the original size of image was too big (:sizeMB).',
    'error_image_not_valid' => 'Google Cloud: sorry, the image is not valid.',
];
