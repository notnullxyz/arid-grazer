<?php
/**
 * ApiVersionTool.php
 * Part of arid-grazer
 *
 * @author: Marlon van der Linde <marlon@notnull.xyz>
 * License: MIT
 */

namespace App\Library;

/**
 * The actual header name for Api-Version.
 */
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

if (!defined('API_VERSION_HEADER_FIELD')) {
    define('API_VERSION_HEADER_FIELD', 'API-Version');
}

/**
 * Class ApiVersionTool
 * Helper functions for getting and checking the existence of api-version in request headers.
 * @package App\Library
 */
class ApiVersionTool
{

    /**
     * Check if the api version header is set, and if so, returns it, else zero is returned.
     * This is now fastcgi/fpm safe, and does not use getallheaders()
     * @return int
     */
    public static function getApiVersionFromHeader() : int
    {
        $key = strtoupper('HTTP_'
            . str_replace('-','_', constant('API_VERSION_HEADER_FIELD')));

        return $_SERVER[$key] ?? 0;

        return $version;
    }

    /**
     * Given an integer api version, checks if this version is supported as set in environment variables in the form:
     * API_SUPPORTED_VERSIONS = 1,2,4
     * @param $apiVersion
     *
     * @return bool
     */
    public static function isSupportedApiVersion($apiVersion) : bool
    {
        if (in_array(intval($apiVersion), explode(',', env('API_SUPPORTED_VERSIONS')))) {
            return true;
        }
        return false;
    }

    /**
     * Returns the api version from request headers, if and only if, the headers were set, contained a valid version
     * number above 0, and is a supported api version.
     *
     * @return int
     */
    public static function validateAndGetApiVersionFromHeader() : int
    {
        $version = self::getApiVersionFromHeader();
        if ($version && self::isSupportedApiVersion($version)) {
            return $version;
        } else {
            Log::debug("[API Version] requested $version FAIL");
            header('HTTP/1.1 412 Pre-Condition Failed', true, 412);
            exit();
        }

    }

}
