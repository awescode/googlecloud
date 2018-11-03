<?php

namespace Awescode\GoogleCloud;

use Awescode\GoogleCloud\App\Encode;
use Awescode\GoogleCloud\App\DecodeURL;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Filesystem as Disk;


class GoogleCloud
{

    /**
     * The cache instance.
     */
    protected $cache;
    protected $config;
    protected $disk;
    protected $locally;


    /*
     * Key for caching links
     */
    protected $cache_key;

    /*
     * Tmp array with main image for <picture>
     */
    protected $picture_main_image;

    // Default values of options
    protected $options = [
        'cropmode' => 'strong',
        'quality' => '75',
        'isretina' => true,
        'extension' => 'jpg',
        'original' => false,   // service option, will use if you want to use original image
        'modify' => []       // service option, modify will use for generation string to google
    ];

    /**
     * Create a new repository instance.
     *
     * @param  Cache $cache
     * @param  array $config
     * @param  Disk $disk
     */
    public function __construct(Cache $cache, array $config, Disk $disk)
    {
        $this->cache = $cache;
        $this->config = $config;
        $this->disk = $disk;
        $this->locally = $this->config['locally'];
    }

    //======================================================================
    // Main functions
    //======================================================================

    /**
     * The function will return an URL without any tags
     *
     * @param $path
     * @param array $option
     * @return string
     */
    public function url($path, $option = [], $isTest = false)
    {
        $key = $this->getCacheKey('url', $path, $option);
        if ($cacheHtml = $this->getCache($key)) {
            return $this->res($cacheHtml, 'URL: from cache');
        }
        $start = microtime(true);

        $this->setOptions($option);

        $file = $this->getUrl($path);
        if (!$this->hasFile($file)) {
            $cacheHtml = $this->urlGenerate($file, 'dynamic', $isTest);
            $end = microtime(true);
            return $this->res($cacheHtml, 'URL: dynamic, time: ' . round(($end - $start) / 1000, 4));
        } else {
            $cacheHtml = $this->urlGenerate($file, 'static', $isTest);
            $this->setCache($key, $cacheHtml);
            $end = microtime(true);
            return $this->res($cacheHtml, 'URL: saved to cache, time: ' . round(($end - $start) / 1000, 4));
        }
    }

    /**
     * The function will return <img /> tag with all necessary parameters and attributes
     *
     * @param $path
     * @param array $option
     * @return string
     */
    public function img($path, $option = [], $isTest = false)
    {
        $key = $this->getCacheKey('image', $path, $option);
        if ($cacheHtml = $this->getCache($key)) {
            return $this->res($cacheHtml, 'IMG: from cache');
        }
        $start = microtime(true);

        $this->setOptions($option);

        $url = $this->getUrl($path, $this->isRetina(-1));

        $file = $this->convertUrl($url);

        if (!$this->hasFile($file)) {
            $file2x = $this->getUrl2x($path);
            $elements = [
                $this->getSrc($file, 'dynamic', $isTest),
                $this->getSrcSet($file2x, 'dynamic', $isTest),
                $this->getAlt(),
                $this->getAttributes()
            ];

            $cacheHtml = $this->getHtmlImg($elements);
            $end = microtime(true);
            return $this->res($cacheHtml, 'IMG: dynamic, time: ' . round(($end - $start) / 1000, 4));
        } else {
            $file2x = $this->getUrl2x($path);
            $elements = [
                $this->getSrc($file, 'static', $isTest),
                $this->getSrcSet($file2x, 'static', $isTest),
                $this->getAlt(),
                $this->getAttributes()
            ];

            $cacheHtml = $this->getHtmlImg($elements);

            $this->setCache($key, $cacheHtml);
            $end = microtime(true);
            return $this->res($cacheHtml, 'IMG: saved to cache, time: ' . round(($end - $start) / 1000, 4));
        }
    }

    /**
     * The function will return a <picture> tag made on $options
     *
     * @param $path
     * @param array $option
     * @return string
     */
    public function picture($path, $option = [], $isTest = false)
    {
        $key = $this->getCacheKey('picture', $path, $option);
        if ($cacheHtml = $this->getCache($key)) {
            return $this->res($cacheHtml, 'PICTURE: from cache');
        }
        $start = microtime(true);

        $this->setOptions($option);

        $sources = $this->getHtmlSources($path);
        $url = $this->getUrlFromOption($path, $this->picture_main_image);

        $file = $this->convertUrl($url);

        if (!$this->hasFile($file)) {

            $attributes = $this->getAttributes();

            $img = $this->getHtmlImg([
                $this->getSrc($url, 'dynamic', $isTest),
                $this->getAlt()
            ]);

            $cacheHtml = $this->getHtmlPicture($attributes, $img, $sources,'dynamic', $isTest);

            $end = microtime(true);
            return $this->res($cacheHtml, 'PICTURE: dynamic, time: ' . round(($end - $start) / 1000, 4));
        } else {

            $attributes = $this->getAttributes();

            $img = $this->getHtmlImg([
                $this->getSrc($url, 'static', $isTest),
                $this->getAlt()
            ]);

            $cacheHtml = $this->getHtmlPicture($attributes, $img, $sources, 'static', $isTest);

            $this->setCache($key, $cacheHtml);
            $end = microtime(true);
            return $this->res($cacheHtml, 'PICTURE: saved to cache, time: ' . round(($end - $start) / 1000, 4));
        }


    }

    //======================================================================
    // Core
    //======================================================================

    /**
     * Generate correct URL from filename
     *
     * @param $file
     * @param $mode
     * @param bool $isTest
     * @return string
     */
    private function urlGenerate($file, $mode, $isTest = false)
    {
        if ($isTest) {
            return $file;
        }
        switch ($mode) {
            case "dynamic":
                return $this->dUrl() . $file;
                break;
            case "static":
                return $this->sUrl() . $file;
                break;
        }
        return $file;
    }

    /**
     * Generate key for cache base on income parameters
     *
     * @param $type
     * @param $path
     * @param $option
     * @return string
     */
    private function getCacheKey($type, $path, $option)
    {
        return md5($type . $path . json_encode($option));
    }

    private function convertUrl($file)
    {
        $gh = new DecodeURL($file);
        $gh->decodeUrl();
        return $gh->image;
    }

    /**
     * Generate <sources> for tag <picture>
     *
     * @param $path
     * @return array
     */
    private function getHtmlSources($path)
    {
        $sources = [];
        $sourceOptions = $this->getCollectOptions();

        $this->saveMainImage($sourceOptions);

        foreach ($this->getOption('srcset') as $item) {
            $sources[] = $this->getSource($path, $sourceOptions, $item);
            $sourceOptions = $this->reorderCollection($sourceOptions);
        }
        return $sources;
    }

    /**
     * Function for storing main image for <picture>. If option main empty it will get first
     *
     * @param $options
     * @return bool
     */
    private function saveMainImage($options)
    {
        foreach($options as $option) {
            $option = (array)$option;
            if (isset($option['main']) && $option['main']) {
                $this->picture_main_image = (object)$option;
                return true;
            }
        }
        if (isset($options[0])) {
            $this->picture_main_image = $options[0];
            return true;
        }
        return false;
    }

    /**
     * Reorder collection of data for <sources>, it need for changing placing when it will build an URL
     *
     * @param $array
     * @return array
     */
    private function reorderCollection($array)
    {
        $array_splice = $array;
        array_splice($array_splice, 0, 1);
        $array_splice[] = $array[0];
        return $array_splice;
    }

    /**
     * Getting all collection options for <sources>
     *
     * @return array
     */
    private function getCollectOptions()
    {
        $collection = [];
        foreach ($this->getOption('srcset') as $item) {
            $collection[] = $this->collectionFiltering($item, $this->options);
        }
        return $collection;
    }

    /**
     * Filtering collection of items from srcset for <picture> tag
     *
     * @param $item
     * @param $options
     * @return array|object
     */
    private function collectionFiltering($item, $options)
    {
        $options = $this->mergeConfigs($item, $options);
        $options = $this->checkModify($options);
        return $this->mappingToEncode($options);
    }

    /**
     * Get <source> tag for <picture>
     *
     * @param $path
     * @param $sourceOptions
     * @param $item
     * @return array
     */
    private function getSource($path, $sourceOptions, $item)
    {
        $options = $this->collectionFiltering($item, $this->options);

        $imgClass = new Encode($path, $options, $this->config);

        return ['file' => $imgClass->getPath(null, $sourceOptions), 'item' => $item];

    }

    /**
     * Generation of HTML for <picture>
     *
     * @param $sources
     * @param $mode
     * @param $isTest
     * @return string
     */
    private function getHTMLFromSources($sources, $mode, $isTest)
    {
        $output = [];
        foreach($sources as $source) {
            $file = $source['file'];
            $item = [];
            if (isset($source['item'])) {
                $item = $source['item'];
            }
            $url = $this->urlGenerate($file, $mode, $isTest);
            $elements = [
                'src="' . $url . '"',
                $this->getAttributes($item)
            ];
            $output[] = '<source ' . implode(" ", $elements) . '>';
        }
        return implode(" ", $output);
    }





    /**
     * Merging configs for <picture> <source> tags
     *
     * @param $config_priority
     * @param $config_secondary
     * @return mixed
     */
    private function mergeConfigs($config_priority, $config_secondary)
    {
        foreach ($config_secondary as $key => $value) {
            if (!isset($config_priority[$key])) {
                $config_priority[$key] = $value;
            }
        }
        return $config_priority;
    }


    /**
     * Get modify variables from options
     *
     * @param array $overwrite
     * @return string
     */
    private function getModify($overwrite = [])
    {
        if ($overwrite == []) {
            $options = $this->options;
        } else {
            $options = $overwrite;
        }
        if ($this->hasOption('modify', $options)) {
            $modify = array_unique($this->getOption('modify', $options));
            return implode("-", $modify);
        }
        return '';
    }

    /**
     * Helper function: Get a value to item
     *
     * @param $name
     * @param array $overwrite
     * @return mixed|null
     */
    private function getOption($name, $overwrite = [])
    {
        if ($overwrite == []) {
            $options = $this->options;
        } else {
            $options = $overwrite;
        }
        if ($this->hasOption($name, $options)) {
            return $options[$name];
        }
        return null;
    }

    /**
     * Helper function: Checking if exist the variable inside of object
     *
     * @param $name
     * @param array $overwrite
     * @return bool|null
     */
    private function hasOption($name, $overwrite = [])
    {
        if ($overwrite == []) {
            $options = $this->options;
        } else {
            $options = $overwrite;
        }
        if (isset($options[$name])) {
            return true;
        }
        return null;
    }

    /**
     * Helper function: Set a value to item
     *
     * @param $name
     * @param $value
     * @param array $overwrite
     * @return array
     */
    private function setOption($name, $value, $overwrite = [])
    {
        if ($overwrite == []) {
            $options = $this->options;
        } else {
            $options = $overwrite;
        }

        $options[$name] = $value;
        if ($overwrite == []) {
            $this->options = $options;
        }
        return $options;
    }

    /**
     * Helper function: Remove a item from option
     *
     * @param $name
     * @param array $overwrite
     * @return array
     */
    private function removeOption($name, $overwrite = [])
    {
        if ($overwrite == []) {
            $options = $this->options;
        } else {
            $options = $overwrite;
        }
        unset($options[$name]);
        if ($overwrite == []) {
            $this->options = $options;
        }
        return $options;
    }

    /**
     * Helper function: Add a new item to option
     *
     * @param $name
     * @param $value
     * @param array $overwrite
     * @return array
     */
    private function addToOption($name, $value, $overwrite = [])
    {
        if ($overwrite == []) {
            $options = $this->options;
        } else {
            $options = $overwrite;
        }
        $options[$name][] = $value;
        if ($overwrite == []) {
            $this->options = $options;
        }
        return $options;
    }

    /**
     * Function for setting up the configs
     *
     * @param array $opt
     * @return bool
     */
    private function setOptions($opt = [])
    {
        if ($opt == []) {
            $this->setOption('original', true);
            $this->setOption('isretina', false);
            return true;
        }
        foreach ($opt as $key => $value) {
            switch ($key) {
                case 'width':
                case 'height':
                    if ((int)$value > 0) {
                        $this->setOption($key, (int)$value);
                    }
                    break;
                case 'alt':
                case 'title':
                case 'class':
                case 'id':
                    if (trim($value) != "") {
                        $this->setOption($key, $this->filterString($value));
                    }
                    break;
                case 'quality':
                    if ((int)$value > 0 && (int)$value <= 100) {
                        $this->setOption($key, (int)$value);
                    }
                    break;
                case 'isretina':
                    if (is_bool($value)) {
                        $this->setOption($key, $value);
                    }
                    break;
                case 'extension':
                    if (in_array($value, ['jpg', 'png', 'webp', 'gif'])) {
                        $this->setOption($key, $value);
                    }
                    break;
                case 'attr':
                    if (is_array($value)) {
                        foreach ($value as $attr_name => $attr_value) {
                            if (!in_array($attr_name, ['src', 'alt', 'title', 'class', 'id'])) {
                                if (!$this->hasOption('attr')) {
                                    $this->setOption('attr', [$attr_name => $this->filterString($attr_value)]);
                                } else {
                                    $this->options['attr'][$attr_name] = $this->filterString($attr_value);
                                }
                            }
                        }
                    }
                    break;
                case 'srcset':
                    if (is_array($value)) {
                        $this->setOption($key, $value);
                    }
                    break;
            }
        }

        if ($this->hasOption('srcset')) {
            // remove some of options elements `width`, ``height`, `cropmode`, `isretina`, original
            $this->removeOption('width');
            $this->removeOption('height');
            $this->removeOption('cropmode');
            $this->removeOption('isretina');
            $this->removeOption('original');
        }

        if (!$this->hasOption('srcset')) {
            $cropmode_key = 'cropmode';
            $cropmode_value = $this->getOption('cropmode');
            if (isset($opt[$cropmode_key])) {
                $cropmode_value = $opt[$cropmode_key];
            }
            $this->options = $this->cropMode($this->options, $cropmode_key, $cropmode_value);
        }

        $this->options = $this->checkModify($this->options);

        if ($this->getModify() == '') {
            $this->setOption('original', true);
        }

    }

    /**
     * Function for finding correct parameters for crop variables
     *
     * @param $option
     * @param $cropmode_key
     * @param $cropmode_value
     * @return array
     */
    private function cropMode($option, $cropmode_key, $cropmode_value)
    {
        switch ($cropmode_value) {
            case 'strong':
                if ($this->getOption('height', $option) != null && $this->getOption('width', $option) != null) {
                    $option = $this->addToOption('modify', 'c', $option);
                    $option = $this->setOption($cropmode_key, $cropmode_value, $option);
                } else {
                    $option = $this->setOption($cropmode_key, 'smallest', $option);
                }
                break;
            case 'center':
                $option = $this->setOption($cropmode_key, $cropmode_value, $option);
                if ($this->getOption('height', $option) != null || $this->getOption('width', $option) != null) {
                    $option = $this->addToOption('modify', 'n', $option);
                }
                break;
            case 'smart':
                $option = $this->setOption($cropmode_key, $cropmode_value, $option);
                if ($this->getOption('height', $option) != null || $this->getOption('width', $option) != null) {
                    $option = $this->addToOption('modify', 'p', $option);
                }
                break;
            case 'smart-alternate':
                $option = $this->setOption($cropmode_key, $cropmode_value, $option);
                if ($this->getOption('height', $option) != null || $this->getOption('width', $option) != null) {
                    $option = $this->addToOption('modify', 'pp', $option);
                }
                break;
            case 'circularly':
                $option = $this->setOption($cropmode_key, $cropmode_value, $option);
                if ($this->getOption('height', $option) != null || $this->getOption('width', $option) != null) {
                    $option = $this->addToOption('modify', 'cc', $option);
                }
                break;
            case 'smallest':
                $option = $this->setOption($cropmode_key, $cropmode_value, $option);
                if ($this->getOption('height', $option) != null || $this->getOption('width', $option) != null) {
                    $option = $this->addToOption('modify', 'ci', $option);
                }
                break;
        }
        return $option;
    }


    /**
     * Function for checking income parameters, converting to modify variables
     *
     * @param $option
     * @return array
     */
    private function checkModify($option)
    {
        if ($this->hasOption('width', $option) && $this->hasOption('height', $option)) {
            $option = $this->addToOption('modify', 'w' . $this->getOption('width', $option), $option);
            $option = $this->addToOption('modify', 'h' . $this->getOption('height', $option), $option);
            $option = $this->addToOption('modify', 'c', $option);
        }

        if ($this->hasOption('width', $option) && !$this->hasOption('height', $option)) {
            $option = $this->addToOption('modify', 's' . $this->getOption('width', $option), $option);
        }

        if (!$this->hasOption('width', $option) && $this->hasOption('height', $option)) {
            $option = $this->addToOption('modify', 's' . $this->getOption('height', $option), $option);
        }

        return $this->addToOption('modify', 'l' . $this->getOption('quality', $option), $option);
    }

    /**
     * Generate <img /> tag with all data
     *
     * @param $elements
     * @return string
     */
    private function getHtmlImg($elements)
    {
        $elements = $this->trimArray($elements);
        return '<img ' . implode(' ', $elements) . ' />';
    }

    /**
     * Generate <picture> tag with all data
     *
     * @param $attributes
     * @param $img
     * @param $sources
     * @return string
     */
    private function getHtmlPicture($attributes, $img, $sources, $mode, $isTest)
    {
        $sourcesHTML = $this->getHTMLFromSources($sources, $mode, $isTest);
        return '<picture' . (($attributes != '') ? (' ' . $attributes) : '') . '>' . $sourcesHTML . $img . '</picture>';
    }


    /**
     * Generating attributes based on options or overwrite array
     *
     * @param array $overwrite
     * @return string
     */
    private function getAttributes($overwrite = [])
    {
        $attrOutput = [];
        $attrsAvailable = ['class', 'id', 'title', 'attr', 'media'];
        foreach ($attrsAvailable as $attr_name) {
            $attr_value = $this->getOption($attr_name, $overwrite);
            if ($attr_value != null) {
                if (is_array($attr_value)) {
                    foreach ($attr_value as $key => $value) {
                        $attrOutput[] = $key . '="' . $value . '"';
                    }
                } else {
                    $attrOutput[] = $attr_name . '="' . $attr_value . '"';
                }
            }
        }

        return implode(" ", $this->trimArray($attrOutput));
    }

    /**
     * Getting Alt tag
     *
     * @return string
     */
    private function getAlt()
    {
        if ($this->hasOption('alt')) {
            return 'alt="' . $this->getOption('alt') . '"';
        }
        return '';
    }

    /**
     * Checking is retina should be or not
     *
     * @param int $coof
     * @return bool|float|int
     */
    private function isRetina($coof = 1)
    {
        if ($this->getOption('isretina')) {
            return 2 * $coof;
        }
        return false;
    }

    /**
     * * Get URL from options
     *
     * @param $path
     * @param bool $doubleSize
     * @return string
     */
    private function getUrl($path, $doubleSize = false)
    {
        $imgClass = new Encode($path, $this->mappingToEncode(), $this->config);
        return $imgClass->getPath($doubleSize);
    }

    /**
     * Get URL from income options
     *
     * @param $path
     * @param $options
     * @return string
     */
    private function getUrlFromOption($path, $options)
    {
        $imgClass = new Encode($path, $options, $this->config);
        return $imgClass->getPath();
    }

    /**
     * Get URL 2x from options
     *
     * @param $path
     * @return string
     */
    private function getUrl2x($path)
    {
        $imgClass = new Encode($path, $this->mappingToEncode(), $this->config);
        return $imgClass->getPath($this->isRetina());
    }

    /**
     * Get src="" tag with correct URL inside
     *
     * @param $file
     * @param string $type
     * @param bool $isCache
     * @return string
     */
    private function getSrc($file, $type = '', $isCache = false)
    {
        $url = $this->urlGenerate($file, $type, $isCache);
        return 'src="' . $url . '"';
    }

    /**
     * Get srcset="" with correct URL inside
     *
     * @param $file
     * @param string $type
     * @param bool $isCache
     * @return string
     */
    private function getSrcSet($file, $type = '', $isCache = false)
    {
        if (!$this->getOption('isretina')) {
            return '';
        }
        $url = $this->urlGenerate($file, $type, $isCache);
        return 'srcset="' . $url . ' 2x"';
    }

    /**
     * Function for mapping the data for getting correct URLs
     *
     * @param array $overwrite
     * @return array|object
     */
    public function mappingToEncode($overwrite = [])
    {
        if ($overwrite == []) {
            $options = $this->options;
        } else {
            $options = $overwrite;
        }

        $options = (object)[
            'modify' => $this->getModify($options),
            'alt' => $this->getOption('alt', $options),
            'title' => $this->getOption('title', $options),
            'ext' => $this->getOption('extension', $options),
            'original' => $this->getOption('original', $options),
            'main' => $this->getOption('main', $options)
        ];

        return $options;
    }

    /**
     * Result output to log. Used it for checking the cache system
     *
     * @param $out
     * @param string $info
     * @return mixed
     */
    private function res($out, $info = '')
    {
        if ($this->config['debug'] && $info) {
            \Debugbar::info($info);
        }
        return $out;
    }


    /**
     * @return mixed
     */
    public function noImg()
    {
        return $this->config['no-image'];
    }

    //======================================================================
    // Support function
    //======================================================================

    /**
     * Get static URL
     *
     * @param bool $original
     * @return string
     */
    private function sUrl()
    {
        return (!$this->getOption('original'))
            ? $this->config['cdn-static'] . '/' . $this->config['thumb_folder'] . '/'
            : $this->config['cdn-static'] . '/';
    }

    /**
     * Get dynamic URL
     *
     * @return string
     */
    private function dUrl()
    {
        return $this->config['cdn-dynamic'] . '/';
    }

//======================================================================
// Test function
//======================================================================


    /**
     * @param $path
     * @param $options
     * @return string
     */
    public function testImg($path, $options)
    {
        return $this->img($path, $options, true);
    }

    /**
     * @param $path
     * @param $options
     * @return string
     */
    public function testPicture($path, $options)
    {
        return $this->picture($path, $options, true);
    }

    /**
     * @param $path
     * @param $options
     * @return string
     */
    public function testUrl($path, $options)
    {
        return $this->url($path, $options, true);
    }


//-----------------------------------------------------
// API methods: Disk
//-----------------------------------------------------

    /**
     * @param $path
     * @return bool
     */
    private function hasFile($path)
    {
        return $this->disk->exists($path);
    }

//-----------------------------------------------------
// API methods: Cache
//-----------------------------------------------------

    /**
     * @param $key
     * @return mixed
     */
    private function getCache($key)
    {
        return $this->cache->get($key);
    }

    /**
     * @param $key
     * @param $val
     */
    private function setCache($key, $val)
    {
        $this->cache->forever($key, $val);
    }

    /**
     * Remove from array empty items
     *
     * @param $array
     * @return array
     */
    private function trimArray($array)
    {
        if (is_array($array) && count($array) > 0) {
            $return = [];
            foreach ($array AS $b) {
                if (!is_array($b)) {
                    if (trim($b) != "") {
                        $return[] = trim($b);
                    }
                }
            }
            return $return;
        } else {
            return [];
        }
    }

    /**
     * Filtering string for Alt or Title tag
     *
     * @param $string
     * @return string
     */
    private function filterString($string)
    {
        $string = htmlentities(trim($string));
        $string = str_replace("'", " ", $string);
        $string = str_replace('"', " ", $string);
        $string = str_replace('&', " ", $string);
        $string = str_replace(';', " ", $string);
        $string = preg_replace('/\s+/', ' ', $string);
        return trim($string);
    }

}
