<?php
/**
 * TokenToolkit.php
 * Part of arid-grazer
 *
 * @author: Marlon
 *
 */

namespace App\Library;

/**
 * Class TokenToolkit
 *
 * This class is a library collection of tools for Token work in Arid Grazer.
 * It's purpose is to decouple specific and reused functionality out of controllers.
 * @package App\Library
 */
class TokenToolkit
{

    /**
     * Gets the configured, or if not set, random, string of salt.
     */
    public static function getSalt() : string
    {
        return getenv('NACL') !== false ? getenv('NACL') : str_random(8);
    }

    public static function makeToken(array $userData, $salt = null) : string
    {
        if (!$salt) {
            $salt = static::getSalt();
        }
        return sha1(json_encode($userData) . strval($salt));
    }

}