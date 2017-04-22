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
            'user-read'         => 'View email and other account info',
            'user-write'        => 'View and manage account',
        ]);
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
