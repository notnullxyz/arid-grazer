<?php

namespace App\Http\Middleware;

use Closure;

class VersionHeader
{
    /**
     * Handle an incoming request to determine the api version headers.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $version = intval($request->header('api-version'));
        if (!$version) {
            return response("Requires API-Version header", 412);
        } else {
            if (in_array($version, explode(',', env('API_SUPPORTED_VERSIONS')))) {
                return $next($request);
            }
            return response("Requested API-Version not available", 404);
        }
    }
}
