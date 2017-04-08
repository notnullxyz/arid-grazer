<?php

namespace App\Services\GrazerRedis;

use Predis\Client;


/**
 * GrazerRedisService.php
 * Part of arid-grazer
 *
 * @author: Marlon
 *
 */
class GrazerRedisService implements IGrazerRedisService
{

    private $client;

    public function __construct()
    {
        $this->client = $this->createClient();
    }

    /**
     * Create and return a PRedis Client.
     * @return Client
     */
    private function createClient() : Client
    {
        // Build config array from .env, or have sane localhost defaults.
        $config = [
            'scheme' => 'tcp',
            'host'   => env('REDIS_HOST', '127.0.0.1'),
            'port'   => env('REDIS_PORT', 6379),
        ];

        return new Client($config);
    }

    public function emailExists($email) : bool
    {
        return $this->client->exists($email);
    }

    public function exists($email, $uniq) : bool
    {
        return $this->client->hexists($email, $uniq);
    }

    /**
     * @inheritDoc
     */
    public function setUser(IGrazerRedisUserVO $user): void
    {
        $email = $user->get()['email'];
        $result = $this->client->hmset($email, $user->get());
        if (!$result) {
            abort(500, 'Something went rotten while setting user hash');
        }
    }

    /**
     * @inheritDoc
     */
    public function getUser(string $emailKey): IGrazerRedisUserVO
    {
        $email = $created = $uniq = $active = null;

        if ($this->client->exists($emailKey)) {
            if (!extract($this->client->hgetall($emailKey))) {
                abort(500, 'Something went rotten while getting user hash');
            }
            return new GrazerRedisUserVO($uniq, $email, $active, $created);
        } else {
            abort(404, "Could not find a hash on this key $emailKey");
        }
    }

    /**
     * @inheritDoc
     */
    public function setPackage(IGrazerRedisPackageVO $package): int
    {
        // TODO: Implement setPackage() method.
    }

    /**
     * @inheritDoc
     */
    public function getPackage(int $packageId): IGrazerRedisPackageVO
    {
        // TODO: Implement getPackage() method.
    }

    /**
     * @inheritDoc
     */
    public function touchPackageTTL(int $packageId, int $ttl): void
    {
        // TODO: Implement touchPackageTTL() method.
    }


}
