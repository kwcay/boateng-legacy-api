<?php
/**
 * Copyright Dora Boateng(TM) 2017, all rights reserved.
 */
namespace App\Providers;

use Laravel\Passport\Passport;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // OAuth scopes
        Passport::tokensCan([
            'resource-read'     => 'View resources',
            'resource-write'    => 'View and manage resources',
            'user-read'         => 'Login and view email address/other account info',
            'user-write'        => 'View and manage account',
        ]);

        // OAuth routes
        Passport::routes();
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
