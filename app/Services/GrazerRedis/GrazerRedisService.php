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
    private $dbIndexUser, $dbUser, $dbIndexPackage, $dbPackage, $dbCounter;

    public function __construct()
    {
        $this->client = $this->createClient();
        $this->dbIndexUser = env('REDIS_DB_INDEX_USER', 3);
        $this->dbUser = env('REDIS_DB_USER', 1);
        $this->dbPackage = env('REDIS_DB_PACKAGE', 2);
        $this->dbIndexPackage = env('REDIS_DB_INDEX_PACKAGE', 4);
        $this->dbCounter = env('REDIS_DB_COUNTER', 5);
        $this->dbTokenStore = env('REDIS_DB_AUTH', 6);

        if (!defined('COUNTER_KEY_USER')) {
            define('COUNTER_KEY_USER', 'users_total_ever');
        }

        if (!defined('COUNTER_KEY_PACKAGE')) {
            define('COUNTER_KEY_PACKAGE', 'packages_total_ever');
        }

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

    /**
     * Check the Index for existence of an email
     * @param $email
     *
     * @return bool
     */
    public function emailExists($email) : bool
    {
        $this->client->select($this->dbIndexUser);
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
        $this->client->select($this->dbUser);
        return $this->client->exists($uniq);
    }

    /**
     * Create an index in the user index.
     * @param $email
     * @param $uniq
     */
    public function userIndexSet($email, $uniq)
    {
        $this->client->select($this->dbIndexUser);
        if (!$this->client->exists($email)) {
            $this->client->set($email, $uniq);
        } else {
            abort(409, "Key '$email' exists in the Grazer Index");
        }

    }

    public function userIndexGet($email)
    {
        $this->client->select($this->dbIndexUser);
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
        $this->client->select($this->dbUser);
        $uniq = $user->get()['uniq'];
        $result = $this->client->hmset($uniq, $user->get());
        if (!$result) {
            abort(500, 'Something went rotten while persisting the user hash');
        }

        $this->userIndexSet($user->get()['email'], $uniq);
        $this->countIncrement(constant('COUNTER_KEY_USER'));
    }

    /**
     * @inheritDoc
     */
    public function getUser(string $uniqKey): IGrazerRedisUserVO
    {
        $this->client->select($this->dbUser);
        $email = $created = $uniq = $active = null;

        if ($this->client->exists($uniqKey)) {
            if (!extract($this->client->hgetall($uniqKey))) {
                abort(500, 'Something went rotten while getting user hash');
            }
            return new GrazerRedisUserVO($uniq, $email, $active, $created);
        } else {
            abort(404, "Could not find a living uniq on this key $uniqKey");
        }
    }

    /**
     * @inheritDoc
     */
    public function setPackage(IGrazerRedisPackageVO $packageVO, string $hash)
    {
        $this->client->select($this->dbPackage);
        $package = $packageVO->get();

        $this->client->hmset($hash, $package);
        $this->client->expire($hash, $package['expire']);
        $this->packageIndexSet($hash, $package['dest'], $package['expire']);
        $this->countIncrement(constant('COUNTER_KEY_PACKAGE'));
    }

    /**
     * @inheritDoc
     */
    public function getPackage(string $packageHash): IGrazerRedisPackageVO
    {
        $this->client->select($this->dbPackage);

        $dest = $label = $expire = $content = $origin = $sent = null;

        if ($this->client->exists($packageHash)) {
            if (!extract($this->client->hgetall($packageHash))) {
                abort(500, 'Something went rotten while getting user hash');
            }

            $expire = $this->client->ttl($packageHash);     // we care about what's remaining.

            return new GrazerRedisPackageVO($origin, $dest, $label, $sent, $expire, $content);
        } else {
            abort(404, "Could not find a package on this hash $packageHash");
        }
    }

    /**
     * @inheritDoc
     */
    public function touchPackageTTL(int $packageId, int $ttl): void
    {
        $this->client->select($this->dbPackage);

    }

    /**
     * Convenience wrapper for checking the existence of a package hash in the package index. No more, no less.
     * @param $hash
     *
     * @return bool
     */
    public function packageExists($hash) : bool
    {
        $this->client->select($this->dbIndexPackage);
        if ($this->client->exists($hash)) {
            return true;
        }
        return false;
    }

    /**
     * Set a package index entry, as package id/hash -> uniq
     *
     * @param $uniqDest Uniq of the receiver of this package.
     * @param $packageHash
     * @param $ttl
     */
    public function packageIndexSet($packageHash, $uniqDest, $ttl)
    {
        $this->client->select($this->dbIndexPackage);
        if (!$this->client->exists($packageHash)) {
            $this->client->set($packageHash, $uniqDest);
            $this->client->expire($packageHash, $ttl);
            return;
        } else {
            abort(409, "Package hash '$packageHash' already exists in the package index");
        }
    }

    /**
     * Retrieve a package index hash->id, if it exists.
     * @param $packageHash
     *
     * @return int Package ID
     */
    public function packageIndexGet($packageHash)
    {
        $this->client->select($this->dbIndexPackage);
        if ($this->client->exists($packageHash)) {
            return (int)$this->client->get($packageHash);
        }
        abort(404, "The package hash $packageHash does not exist in the package index");
    }

    /**
     * Persist the token and its associated data to the datastore
     * @param string $token
     * @param array  $tokenData
     */
    public function setApiAccessTokenData(string $token, array $tokenData) {
        $this->client->select($this->dbTokenStore);
        if ($this->client->exists($token)) {
            abort(409, 'Token is already present. Not sure you should ever see this error.');
        }
        $this->client->hmset($token, $tokenData);
    }

    /**
     * Returns the hash set on this token, if it exists, else an empty array is returned.
     * @param $token
     *
     * @return array
     */
    public function getApiAccessTokenData($token) : array {
        $this->client->select($this->dbTokenStore);
        if ($this->client->exists($token)) {
            return $this->client->hgetall($token);
        }
        return [];
    }


    /**
     * Internal function to increment a counter in the datastore.
     * @param string $key The key to increment
     */
    private function countIncrement($key) : int
    {
        $this->client->select($this->dbCounter);
        return intval($this->client->incr($key));
    }

}
