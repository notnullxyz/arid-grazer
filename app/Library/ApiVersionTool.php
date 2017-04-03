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
     * Check if the api version header is set, and if so, if it contains a valid version (and not zero) returns it.
     * Otherwise, return zero.
     * @return int
     */
    public static function getApiVersionFromHeader() : int
    {
        $headers = getallheaders();
        if (is_array($headers)) {
            foreach (getallheaders() as $header => $value) {
                if (strcasecmp($header, API_VERSION_HEADER_FIELD) == 0) {
                    return intval($value) ?? 0;
                }
            }
        }
        return 0;
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
            header('HTTP/1.1 412 Pre-Condition Failed', true, 412);
            exit();
        }

    }

}
