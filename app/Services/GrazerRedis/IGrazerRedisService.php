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
     *
     * @param string $uniq
     *
     * @return IGrazerRedisUserVO
     */
    public function getUser(string $uniq) : IGrazerRedisUserVO;

    /**
     * Creates a package in the courier system.
     * @param IGrazerRedisPackageVO $package
     *
     */
    public function setPackage(IGrazerRedisPackageVO $package, string $hash);

    /**
     * Retrieves a package from the system, by its internal package Hash
     * @param int $packageHash
     *
     * @return IGrazerRedisPackageVO
     */
    public function getPackage(string $packageHash) : IGrazerRedisPackageVO;


    /**
     * Update the storage life/expiry of a package in the system, in seconds.
     * @param int $packageId
     * @param int $ttl
     *
     * @return int (1 if ttl was set, 0 if it did not exist or could not be set)
     */
    public function touchPackageTTL(int $packageId, int $ttl) : int;

    /**
     * Update the TTL/lifetime of a token in the datastore, in seconds.
     * Return 1 if set, or 0 if not found/something bad happens.
     *
     * @param string $keyAsToken
     * @param int    $ttl
     *
     * @return int
     */
    public function touchTokenTTL(string $keyAsToken, int $ttl) : int;
}