<?php
/**
 * GrazerRedisUser.php
 * Part of arid-grazer
 *
 * @author: Marlon van der Linde <marlon@notnull.xyz>
 *
 */

namespace App\Services\GrazerRedis;

use Psr\Log\InvalidArgumentException;

/**
 * Class GrazerRedisUser of type GrazerRedisVO
 *
 * This represents value of an Arid-Grazer User, immutably.
 *
 * @package App\Services\GrazerRedis
 */
final class GrazerRedisUserVO implements IGrazerRedisUserVO
{
    private $uniq;
    private $email;
    private $created;
    private $active;

    /**
     * GrazerRedisUser constructor.
     *
     * @param string $uniq A uniq for a GrazerRedisUser 6-32 chars A-Za-z
     * @param string $email
     * @param bool $active
     */
    public function __construct(string $uniq, string $email, bool $active = true, $created = null)
    {
        $this->uniq = $uniq;
        $this->email = $email;
        $this->active = $active;
        $this->created = $created;
    }

    /**
     * Returns a GrazerRedisUserVO
     * @return array
     */
    public function get() : array
    {
        return [
            'email' => $this->email,
            'uniq' => $this->uniq,
            'created' => $this->created,
            'active' => $this->active
        ];
    }

    /**
     * string overload: returns json representation of the user vo.
     * @return string
     */
    public function __toString() : string
    {
        return json_encode($this->get());
    }

}
