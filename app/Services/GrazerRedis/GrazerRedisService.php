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
    private $dbIndex, $dbUser, $dbPackage;

    public function __construct()
    {
        $this->client = $this->createClient();
        $this->dbIndex = env('REDIS_DB_INDEX', 3);
        $this->dbUser = env('REDIS_DB_USER', 1);
        $this->dbPackage = env('REDIS_DB_PACKAGE', 2);
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

    /**
     * Check a given uniq for existence.
     * @param $email
     * @param $uniq
     *
     * @return bool
     */
    public function exists($uniq) : bool
    {
        $this->$this->client->select($this->dbUser);
        return $this->client->exists($uniq);
    }

    /**
     * Create an index in the user index.
     * @param $email
     * @param $uniq
     */
    public function userIndexSet($email, $uniq)
    {
        $this->$this->client->select($this->dbIndex);
        if (!$this->client->exists($email)) {
            $this->client->set($email, $uniq);
        } else {
            abort(409, "Key '$email' exists in the Grazer Index");
        }

    }

    public function userIndexGet($email)
    {
        $this->$this->client->select($this->dbIndex);
        if ($this->client->exists($email)) {
            $this->client->get($email);
        } else {
            abort(404, "Key '$email' does not exist in the Grazer Index");
        }

    }

    /**
     * @inheritDoc
     */
    public function setUser(IGrazerRedisUserVO $user): void
    {
        $uniq = $user->get()['uniq'];
        $result = $this->client->hmset($uniq, $user->get());
        if (!$result) {
            abort(500, 'Something went rotten while persisting the user hash');
        }
        $this->userIndexSet($user->get()['email'], $uniq);
    }

    /**
     * @inheritDoc
     */
    public function getUser(string $uniqKey): IGrazerRedisUserVO
    {
        $email = $created = $uniq = $active = null;

        if ($this->client->exists($uniqKey)) {
            if (!extract($this->client->hgetall($uniqKey))) {
                abort(500, 'Something went rotten while getting user hash');
            }
            return new GrazerRedisUserVO($uniq, $email, $active, $created);
        } else {
            abort(404, "Could not find a hash on this key $uniqKey");
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
