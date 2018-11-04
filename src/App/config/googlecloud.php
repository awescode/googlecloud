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
    'thumb_folder' => 'thumb',
    'slug_length' => 60,
    'locally' => 'de',
    'storage' => 'gcs',
    'is_not_image' => '<!-- google cloud: :path - is not an image -->',
    'no_image' => '<!-- google cloud: no image -->'
];
