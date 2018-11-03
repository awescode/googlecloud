<?php
namespace Awescode\GoogleCloud\Tests;
use Awescode\GoogleCloud\Providers\GoogleCloudServiceProvider;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
abstract class AbstractTestCase extends OrchestraTestCase
{
    protected function getPackageProviders($app)
    {
        return [
            GoogleCloudServiceProvider::class,
        ];
    }
}