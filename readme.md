# GoogleCloud

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

This is where your description should go. Take a look at [contributing.md](contributing.md) to see a to do list.

## Installation

### Laravel

Via Composer

``` bash
$ composer require awescode/googlecloud
```

The package will automatically register itself.

You can publish the config file with:

```bash
php artisan vendor:publish --provider="Awescode\GoogleCloud\Providers\GoogleCloudServiceProvider" --tag="config"
```

### Google App

```bash
cd ./src/App
composer install
gcloud app deploy --project={project_id}
```

## Available options
The all variables are `optional`.
```php
$options = [
    'width'     => '640',
    'height'    => '480',
    /* 
    * by default is `strong`
    * Availabe:
    * `strong` - crops image to provided dimensions, but if exists `width` and `height`.
    * `center` - same as `strong`, but crops from the center
    * `smart` - square crop, attempts cropping to faces
    * `smart-alternate` — alternate smart square crop, does not cut off faces
    * `circularly` - generates a circularly cropped image
    * `smallest` - square crop to smallest of: width, height
    */
    'cropmode'  => 'strong',         
    'alt'       => 'The text for alt attribute', // default: empty
    'title'     => 'The title for title attribute', // default: empty
    'class'     => 'css-class-1 css-class-2', // default: empty
    'id'        => 'unique-id', // default: empty
    'quality'   => '75',   // default: 75
    'isretina'  => true,    // default: true
    'extension' => 'jpg',   // default: jpg - forces the resulting image to be JPG. Available: jpg, png, webp, gif
    'attr'      => [],      // default: empty - all additional attributes to HTML (src, alt, title, class, id will be ignored)
    /* 
        * by default is empty
        * Available only for <picture> tag
        * `width`, `height`, `cropmode`, `isretina`, `original` will be ignered
    */    
    'srcset'    => [        
        [
            'media' => '(max-width: 375px)',
            'class' => 'class-375',
            'width' => 375,
            'height' => 671,
        ],
        [
            'media' => '(max-width: 750px)',
            'class' => 'class-750',
            'width' => 750,
            'height' => 1342,
        ],
        [
            'media' => '(max-width: 1366px)',
            'width' => 1366,
            'height' => 650,
            'main' => true   // this image will be issed for <img /> tag, if the param is missing, will use first from array 
        ],
        [
            'media' => '(max-width: 2732px)',
            'width' => 2732,
            'height' => 1300,
        ]
    ]
];
```

## Methods

##### Return `<img />` tag
```php
    echo GoogleCloud::img($path, $options = []);
```
##### Return `<picture />` of the image
```php 
    echo GoogleCloud::picture($path, $options = []);
```    

##### Return `URL` of the image
```php
    echo GoogleCloud::url($path, $options = []);
```

## Testing

You can run the tests with:

```bash
composer test
```

## Technology
The library based on Google Photo Cropper.
More about it you can read: https://stackoverflow.com/questions/25148567/list-of-all-the-app-engine-images-service-get-serving-url-uri-options

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email y.lisovenko@awescode.de instead of using the issue tracker.

## Credits

- [theAlex][link-author]
- [Yevhen Lisovenko][link-author]
- [All Contributors][link-contributors]

## License

GPL-3.0-or-later. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/awescode/geolocation.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/awescode/geolocation.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/awescode/geolocation/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/awescode/geolocation
[link-downloads]: https://packagist.org/packages/awescode/geolocation
[link-travis]: https://travis-ci.org/awescode/geolocation
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/awescode
[link-contributors]: ../../contributors]
