<?php

namespace Awescode\GoogleCloud\Tests;

// use the following namespace
use Orchestra\Testbench\TestCase as OrchestraTestCase;

use Awescode\GoogleCloud\GoogleCloud;
use Awescode\GoogleCloud\App\Encode;

//Mock
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Filesystem\Filesystem as Disk;

class GoogleCloudTest extends OrchestraTestCase
{
    private $config;
    private $counter = 0;

    public function setUp()
    {
        parent::setUp();

        $this->config = (include './src/App/config/googlecloud.php');

    }

    public function initGoogleCloud()
    {
        return new GoogleCloud($this->createMock(Cache::class), $this->config, $this->createMock(Disk::class));
    }

//-----------------------------------------------------
// Test section
//-----------------------------------------------------

    /**
     * Test of generator for checking a hash
     */
    public function testHash()
    {
        $this->logStart();
        foreach ($this->listUrls('data') as $object) {

            if (isset($object['out_picture'])) {
                // Check <picture> tag
                $googleImage = $this->initGoogleCloud();
                $testPicture = $googleImage->testPicture($object['in_path'], $object['in_options']);
                $this->assertEquals($this->replaceHash($object['out_picture']), $this->replaceHash($testPicture));
                unset($googleImage, $testPicture);

                $this->log("The test for checking <picture> " . $object['in_path']);
            }


            if (isset($object['out_img'])) {
                // Check <img /> tag
                $googleImage = $this->initGoogleCloud();
                $testImg = $googleImage->testImg($object['in_path'], $object['in_options']);


                if (isset($object['in_options']['isretina']) && $object['in_options']['isretina'] == true) {
                    $secretHashArray = [
                        (new Encode($object['in_path'], $googleImage->mappingToEncode(), $this->config))->testSecretHash(-2),
                        (new Encode($object['in_path'], $googleImage->mappingToEncode(), $this->config))->testSecretHash(2)
                    ];
                } else {
                    $secretHashArray = [
                        (new Encode($object['in_path'], $googleImage->mappingToEncode(), $this->config))->testSecretHash(),
                    ];
                }
                $this->assertEquals(str_replace(['|secretHash|', '|secretHash2|'], $secretHashArray, $object['out_img']), $testImg);
                unset($googleImage, $secretHash, $testImg);

                $this->log("The test for checking <img /> " . $object['in_path']);
            }


            if (isset($object['out_url'])) {
                // Check URL
                $googleImage = $this->initGoogleCloud();
                $testUrl = $googleImage->testUrl($object['in_path'], $object['in_options']);
                $secretHash = (new Encode($object['in_path'], $googleImage->mappingToEncode(), $this->config))->testSecretHash();
                $this->assertEquals(str_replace('|secretHash|', $secretHash, $object['out_url']), $testUrl);
                unset($googleImage, $secretHash, $testUrl);


                $this->log("The test for checking URL " . $object['in_path']);
            }

        }
        $this->logEnd();
    }

//-----------------------------------------------------
// Support function
//-----------------------------------------------------

    /**
     * @param $html
     * @return null|string|string[]
     */
    public function replaceHash($html)
    {
        return preg_replace("/_[a-f0-9]{8}_/ui", '_|secretHash|_', $html);
    }

    /**
     * @param $file
     * @return mixed
     */
    public function listUrls($file)
    {
        return json_decode(file_get_contents('./tests/' . $file . '.json'), TRUE);
    }

    /**
     *
     */
    public function logStart()
    {
        echo PHP_EOL . PHP_EOL . "// ---------- Tests started at " . date("Y-m-d H:i:s") . ' -----------//'.PHP_EOL.PHP_EOL;
    }

    /**
     *
     */
    public function logEnd()
    {
        echo PHP_EOL . "// ---------- " . $this->counter . " tests were done at " . date("Y-m-d H:i:s") . ' -------------//'.PHP_EOL.PHP_EOL;
    }

    /**
     * @param $string
     */
    public function log($string)
    {
        echo "[TEST PASSED] " . $string . ' - OK!' . PHP_EOL;
        $this->counter++;
    }

    /**
     * @param $string
     * @return string
     */
    public function printJson($string)
    {
        return json_encode($string);
    }

}