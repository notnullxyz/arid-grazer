<?php
/**
 * GrazerRedisServiceProvider.php
 * Part of arid-grazer
 *
 * @author: Marlon van der Linde <marlon@notnull.xyz>
 *
 */

namespace App\Providers;

use App\Services\GrazerRedis\GrazerRedisService;
use Illuminate\Support\ServiceProvider;

/**
 * Class RedisServiceProvider
 * Supplies a RedisService instance
 * @package App\Providers
 */
class GrazerRedisServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->singleton(GrazerRedisService::class, function ($app) {
            return new GrazerRedisService($app);
        });
    }

}