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
    private $content;

    public function __construct($origin, $destination, $label, $sent, $content)
    {
        $this->origin = $origin;
        $this->destination = $destination;
        $this->label = $label;
        $this->sent = $sent;
        $this->content = $content;
    }

    /**
     * Returns a GrazerRedisPackageVO
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
     * string overload: returns json representation of the package vo.
     * @return string
     */
    public function __toString() : string
    {
        return json_encode($this->get());
    }

}