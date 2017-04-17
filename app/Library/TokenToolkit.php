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
        $spice = strval(uniqid(time()));
        return sha1(json_encode($userData) . strval($salt) . $spice);
    }

    /**
     * Notify a uniq about a new token, and send with this an OTP.
     * @param $uniq
     * @param $token
     */
    public static function notifyAndSendOTP(string $uniq, string $token)
    {
        // @todo
        print " - Notifying a uniq $uniq about his token $token and otp... TODO - ";
    }

    /**
     * Generate a simple, suitable OTP.
     * @param int $lenMin
     * @param int $lenMax
     *
     * @return string
     */
    public static function makeSimpleOTP(int $lenMin = 4, int $lenMax = 6)
    {
        return bin2hex(random_bytes(rand(4,6)));
    }
}