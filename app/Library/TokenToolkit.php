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
    const TOKEN_MAIL_SUBJECT = 'Arid-Grazer Token Verification';

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
     * Notify a uniq about a new token, and send with this an OTP. A false return value means something was not kosher.
     * On success, the email message string is returned.
     *
     * @param $uniq
     * @param $token
     * @param $email
     * @param $otp
     *
     * @return bool
     *
     * @throws \ErrorException
     */
    public static function notifyAndSendOTP(string $uniq, string $token, string $email, $otp)
    {
        $from = getenv('MAIL_FROM');
        if (!$from) {
            return false;
        }

        $to      = $email;
        $subject = static::TOKEN_MAIL_SUBJECT;
        $headers = "From: $from" . "\r\n" .
            "Reply-To: $from" . "\r\n" .
            'X-Mailer: Arid-Grazer-PHP/' . phpversion();

        $message = sprintf( 'A client on the Arid-Grazer system, claiming to be uniq "%s", actuated the 
            create of a new token [%s]. To enable this token, and replace all previous tokens, fire off a verification
            with the otp "%s"',
            $uniq,
            $token,
            $otp
        );

        mail($to, $subject, $message, $headers);
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