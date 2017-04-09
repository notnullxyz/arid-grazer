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

        // If not an array, and valid json, store as is, else encode it. - for future use to support complex payloads
        //        if (!is_array($content)) {
        //            json_decode($content);
        //           $this->content = (json_last_error() == JSON_ERROR_NONE) ? strval($content) : json_encode($content);
        //        } else {
        //            // if it's an array (origin sent us some json inside content... we just encode and move on.
        //            $this->content = json_encode($content);
        //        }

        $this->content = $content;
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
            'content' => $this->content,
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