<?php

function env($variable, $default)
{
    if (getenv($variable) == '') {
        return $default;
    }

    return getenv($variable);
}

function grab_image($url)
{

    $context = stream_context_create([
        'http'=> [
            'method'=>"GET",
            'header'=>"Accept-language: en\r\n"
        ]
    ]);

    // Open the file using the HTTP headers set above
    $file = file_get_contents($url, false, $context);
    if ($file) {
        return $file;
    }
    return false;
}
