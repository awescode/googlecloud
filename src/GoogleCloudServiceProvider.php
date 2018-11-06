<?php

namespace Awescode\GoogleCloud;

use Illuminate\Support\ServiceProvider;

class GoogleCloudServiceProvider extends ServiceProvider
{

    /**
     * Bootstrap the additional application helpers.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/App/config/googlecloud.php' => config_path('googlecloud.php'),
        ], 'config');
    }

    /**
     * Register the additional application helpers.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('googlecloud', function($app)
        {
            $this->mergeConfigFrom(__DIR__.'/App/config/googlecloud.php', 'googlecloud');
            //$config = $app->config->get($this->config);
            $config = config('googlecloud');
            $storage = app(\Storage::class);
            $disk = $storage::disk(config('googlecloud.storage'));
            return new GoogleCloud($app->cache->driver(), $config, $disk);
        });
    }

}
