<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\GrazerRedis\GrazerRedisService;
use Validator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register our custom validation in our service provider
     *
     */
    public function boot()
    {
        Validator::extend('email_unique', 'App\Validators\UserValidator@isEmailUnique');
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
