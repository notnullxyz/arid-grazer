<?php

namespace App\Providers;

use App\User;
use Illuminate\Support\ServiceProvider;
use Predis\Client;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Boot the authentication services for the application.
     *
     * @return void
     */
    public function boot(Client $client)
    {
        // Here you may define how you wish users to be authenticated for your Lumen
        // application. The callback which receives the incoming request instance
        // should return either a User instance or null. You're free to obtain
        // the User instance via an API token or any other method necessary.

//        $this->app['auth']->viaRequest('api', function ($request) {
//            if ($request->input('api_token')) {
//                return User::where('api_token', $request->input('api_token'))->first();
//            }
//        });

        $client->select(getenv('REDIS_DB_AUTH'));


        $this->app['auth']->viaRequest('api', function ($request) {
            $header = $request->header('Api-Token');

            // bringing redis in, or calling out to it?

//            if ($header && check here.) {
//                return new User(); // return user where in redis....
//            }

        });



    }
}
