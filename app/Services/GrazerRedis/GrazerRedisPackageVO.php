<?php
/**
 * GrazerRedisPackageVO.php
 * Part of arid-grazer
 *
 * @author: Marlon
 *
 */

namespace App\Services\GrazerRedis;


class GrazerRedisPackageVO implements IGrazerRedisPackageVO
{

    private $origin;
    private $destination;
    private $label;
    private $sent;
    private $expire;
    private $content;

    public function __construct(string $origin, string $destination, string $label, $sent, $expire, $content)
    {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->label = $label;
        $this->sent = $sent;
        $this->content = json_encode($content);
        $this->expire = $expire;
    }

    /**
     * Returns a GrazerRedisPackageVO
     * @return array
     */
    public function get() : array
    {
        return [
            'origin' => $this->origin,
            'dest' => $this->destination,
            'label' => $this->label,
            'sent' => $this->sent,
            'expire' => $this->expire,
            'content' => $this->content
        ];
    }

    /**
     * string overload: returns json representation of the package vo.
     * @return string
     */
    public function __toString() : string
    {
        return json_encode($this->get());
    }

}