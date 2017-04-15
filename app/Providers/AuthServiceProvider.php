<?php namespace App\Providers;

use App\Services\GrazerRedis\GrazerRedisService;
use Illuminate\Support\ServiceProvider;

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
     * @param GrazerRedisService $client by injection.
     * @return void
     */
    public function boot(GrazerRedisService $client)
    {

        $this->app['auth']->viaRequest('api', function ($request) use ($client) {
            $header = $request->header('Api-Token');
            $tokenData = $client->getApiAccessTokenData($header);

            if ($tokenData && count($tokenData) && $tokenData['active']) {
                return true;
            }

            return null;
        });
    }
}
