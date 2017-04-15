<?php
/**
 * RouteLog.php
 * Part of arid-grazer
 *
 * @author: Marlon
 *
 */

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;


/**
 * Class RouteLog
 * This logs all requests as specifically as needed.
 *
 * @package App\Http\Middleware
 */
class RouteLog
{
    protected $start;
    protected $end ;

    public function handle($req, Closure $next)
    {
        return $next($req);
    }

    public function terminate($req, $response)
    {
        $this->log($req);
    }

    protected function log($req)
    {
        $ip = $req->ip();
        $url = $req->fullUrl();
        $method = $req->method();

        $log = "[route] {$ip} | {$method} {$url}";
        Log::info($log);
    }

}