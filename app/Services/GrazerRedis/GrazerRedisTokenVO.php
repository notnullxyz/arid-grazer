<?php
/**
 * GrazerRedisTokenVO.php
 * Part of arid-grazer
 *
 * @author: Marlon van der Linde <marlon@notnull.xyz>
 *
 */

namespace App\Services\GrazerRedis;


/**
 * Class GrazerRedisUser of type GrazerRedisVO
 *
 * This represents value of an Arid-Grazer User, immutably.
 *
 * @package App\Services\GrazerRedis
 */
final class GrazerRedisTokenVO implements IGrazerRedisTokenVo
{
    private $uniq;
    private $email;
    private $created;
    private $active;
    private $note;

    /**
     * GrazerRedisTokenVO constructor.
     *
     * @param string $uniq A uniq assigned to a GrazerRedisToken
     * @param string $email
     * @param int   $active
     * @param null   $created
     * @param null   $note A free field to leave 'last notes' for any auditing reasons, use __FUNCTION__ when in doubt
     */
    public function __construct(string $uniq, string $email, int $active = 0, $created = null, $note = null)
    {
        $this->uniq = $uniq;
        $this->email = $email;
        $this->active = $active;
        $this->created = $created;
        $this->note = $note;
    }

    /**
     * Returns a GrazerRedisTokenVO
     * @return array
     */
    public function get() : array
    {
        return [
            'email' => $this->email,
            'uniq' => $this->uniq,
            'created' => $this->created,
            'active' => $this->active,
            'note' => $this->note
        ];
    }

    /**
     * string overload: returns json representation of the token vo.
     * @return string
     */
    public function __toString() : string
    {
        return json_encode($this->get());
    }

}
