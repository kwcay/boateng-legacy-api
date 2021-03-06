<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * This namespace is applied to your controller routes.
     *
     * In addition, it is set as the URL generator's root namespace.
     *
     * @var string
     */
    protected $namespace = 'App\Http\Controllers';

    /**
     * Define your route model bindings, pattern filters, etc.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();
    }

    /**
     * Define the routes for the application.
     *
     * @return void
     */
    public function map()
    {
        Route::middleware('api')
            ->namespace($this->namespace)
            ->group(base_path('routes/api.php'));

        // API v0.4
        Route::prefix('0.4')
            ->middleware('read')
            ->namespace('App\Http\Controllers\v0_4')
            ->group(base_path('routes/0.4.php'));

        // API v0.5
        Route::prefix('0.5')
            ->middleware('read')
            ->namespace('App\Http\Controllers\v05')
            ->group(base_path('routes/0.5.php'));
    }
}
