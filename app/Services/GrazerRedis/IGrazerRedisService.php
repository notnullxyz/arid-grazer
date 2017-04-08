<?php

namespace App\Services\GrazerRedis;

use App\Services\GrazerRedis\IGrazerRedisPackageVO;

/**
 * IGrazerRedisService.php
 * Part of arid-grazer
 *
 * @author: Marlon
 *
 */
interface IGrazerRedisService
{

    /**
     * Creates or updates a user on the system.
     * @param IGrazerRedisUserVO $user
     *
     * @return mixed
     */
    public function setUser(IGrazerRedisUserVO $user) : void;

    /**
     * Retrieves a user by its email, from the system
     * @param string $email
     *
     * @return IGrazerRedisUserVO
     */
    public function getUser(string $email) : IGrazerRedisUserVO;

    /**
     * Creates a package in the courier system and returns its internal id.
     * @param IGrazerRedisPackageVO $package
     *
     * @return int
     */
    public function setPackage(IGrazerRedisPackageVO $package) : int;

    /**
     * Retrieves a package from the system, by its internal package ID
     * @param int $packageId
     *
     * @return IGrazerRedisPackageVO
     */
    public function getPackage(int $packageId) : IGrazerRedisPackageVO;


    /**
     * Update the storage life/expiry of a package in the system.
     * @param int $packageId
     * @param int $ttl
     */
    public function touchPackageTTL(int $packageId, int $ttl) : void;

}