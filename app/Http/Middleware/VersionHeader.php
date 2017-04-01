<?php

namespace App\Http\Middleware;

use Closure;

define("PRECONDITION_FAILED", 412);
define("NOT_FOUND", 412);

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
            return response("Requires API-Version header", PRECONDITION_FAILED);
        } else {
            if (in_array($version, explode(',', env('API_SUPPORTED_VERSIONS')))) {
                return $next($request);
            }
            return response("Requested API-Version not available", NOT_FOUND);
        }
    }
}
