<?php

namespace App\Http\Middleware;

use Closure;

define("PRECONDITION_FAILED", 412);
define("NOT_FOUND", 412);

class VersionHeader
{
    /**
     * Handle an incoming request to determine the api version headers. Responds with the version set or an appropriate
     * failing http response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param   mixed   $apiVersion
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $version = intval($request->header('api-version'));
        if (!$version) {
            return response("Requires API-Version header", PRECONDITION_FAILED);
        } else {
            if (in_array($version, explode(',', env('API_SUPPORTED_VERSIONS')))) {
                // Set or do something with the final version here, then move on...
                return $next($request, $version);
            }
            return response("Requested API-Version not available", NOT_FOUND);
        }
    }
}
