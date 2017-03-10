<?php
/**
 * Copyright Dora Boateng(TM) 2016, all rights reserved.
 */
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class ObfuscatorServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('Obfuscator', function ($app) {
            return new \App\Services\ObfuscatorService;
        });
    }

    /**
     * Returns the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['Obfuscator'];
    }
}
