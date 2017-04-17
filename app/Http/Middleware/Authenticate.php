<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as Auth;
use Illuminate\Support\Facades\Log;

class Authenticate
{
    /**
     * The authentication guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(Auth $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request. Prior to all routes
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null)
    {
        $ip = $request->ip();
        $url = $request->url();
        $method = $request->method();

        $log = "[auth-mw] {$ip} | {$method} {$url}";

        if ($this->auth->guard($guard)->guest()) {
            Log::info($log . " | auth: fail");
            return response('Unauthorized.', 401);
        }
        Log::info($log . " | auth: ok");
        return $next($request);
    }
}
