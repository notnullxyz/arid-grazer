<?php

/**
 * Arid-Grazer Engine - A Multi-User messaging system, with a Post Office like smell.
 * Copyright (C) 2017  Marlon B van der Linde
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace App\Services\GrazerRedis;

use Illuminate\Support\Facades\Log;
use Predis\Client;


/**
 * GrazerRedisService.php
 * This class has become a dumpster for redis abstractions. Don't add to the mess, clean up if you can.
 * Part of arid-grazer
 *
 * @author: Marlon
 *
 */
class GrazerRedisService implements IGrazerRedisService
{

    private $client;
    private $dbIndexUser, $dbUser, $dbIndexPackage, $dbPackage, $dbCounter, $dbTokenStore, $dbUniqPackageLink;

    public function __construct()
    {
        $this->client = $this->createClient();
        $this->dbIndexUser = env('REDIS_DB_INDEX_USER', 3);
        $this->dbUser = env('REDIS_DB_USER', 1);
        $this->dbPackage = env('REDIS_DB_PACKAGE', 2);
        $this->dbUniqPackageLink = env('REDIS_DB_UNIQ_PACKAGE_LINK', 7);
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
    public function uniqExists($uniq) : bool
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
    public function updateUser(IGrazerRedisUserVO $user, $email): IGrazerRedisUserVO
    {   
        
        $revisionUser = $user->get();

        $this->client->select($this->dbUser);
        $uniq = $revisionUser['uniq'];

        $user = new GrazerRedisUserVO($uniq, $email, $revisionUser['active'], $revisionUser['created'], microtime(true));

        $result = $this->client->hmset($uniq, $user->get());
        if (!$result) {
            abort(500, 'Something went rotten while updating hash');
        }
        return $user;
    }

    /**
     * @inheritDoc
     */
    public function getUser(string $uniqKey): IGrazerRedisUserVO
    {
        $this->client->select($this->dbUser);
        $email = $created = $uniq = $active = null;

        if ($this->client->exists($uniqKey) && $uniqKey !== '') {
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
     * Retrieves labels, origins and expiry for each package hash inbound for the provided Uniq.
     *
     * @param string $uniq
     *
     * @return array
     */
    public function getAllPackageLabelsForUniq(string $uniq): array
    {
        $start = 0;
        $packageMap = array();

        $this->client->select($this->dbUniqPackageLink);
        $stop = $this->getPackageCountForUniq($uniq);

        if ($stop > 0) {
            $packageCrate = $this->client->lrange($uniq, $start, $stop);

            // Sure, got some packages, switch to package db, and get some labels
            $this->client->select($this->dbPackage);

            foreach($packageCrate as $packageHash) {
                    $label = $this->client->hget($packageHash, "label");        // topic label
                    $origin = $this->client->hget($packageHash, "origin");                // from
                    $expiryHours = round(floatval($this->client->ttl($packageHash) / (60*60)), 2); // hours of expiry

                $packageMap[$packageHash] = array(
                    'label' => $label,
                    'origin' => $origin,
                    'expiryHours' => $expiryHours
                );
            }
        }
        return $packageMap;
    }

    /**
     * Return the number of packages on the system, for this given uniq.
     * @param $uniq
     *
     * @return int
     */
    public function getPackageCountForUniq($uniq): int
    {
        $this->client->select($this->dbUniqPackageLink);
        return (int)$this->client->llen($uniq);
    }

    /**
     * @inheritDoc
     */
    public function touchPackageTTL(int $packageId, int $ttlSeconds): int
    {
        $this->client->select($this->dbPackage);
        return $this->client->expire($packageId, $ttlSeconds);
    }

    /**
     * @inheritDoc
     */
    public function touchTokenTTL(string $keyAsToken, int $ttlSeconds): int
    {
        $this->client->select($this->dbTokenStore);
        return $this->client->expire($keyAsToken, $ttlSeconds);
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
     * Return the uniq that a package (by hash) is intended for, this would almost always be the recipient.
     * This queries the package-index db.
     *
     * @param string $hash Package hash to check on.
     *
     * @return string
     */
    public function getPackageRecipient($hash) : string
    {
        $this->client->select($this->dbIndexPackage);
        if ($this->packageExists($hash)) {
            return $this->client->get($hash);
        }
        abort(404, "Could not find that package, let one a recipient.', $hash");
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

            // This sets the lookup table for uniq->[]packageHash - Keep pushing more to a list.
            $this->client->select($this->dbUniqPackageLink);
            $this->client->rpush($uniqDest, $packageHash);
            // Cannot set a TTL per list element here... need to think about a sweeping process.

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
    public function setApiAccessTokenData(string $token, IGrazerRedisTokenVO $tokenData)
    {
        $this->client->select($this->dbTokenStore);
        if ($this->client->exists($token)) {
            abort(409, 'Token is already present. Not sure you should ever see this error.');
        }
        $this->client->hmset($token, $tokenData->get());
    }

    /**
     * Returns the hash set on this token, if it exists, else an empty array is returned.
     * @param $token
     *
     * @return array
     */
    public function getApiAccessTokenData($token) : array
    {
        $this->client->select($this->dbTokenStore);
        if ($this->client->exists($token)) {
            return $this->client->hgetall($token);
        }
        return [];
    }


    /**
     * Activate the provided token.
     * @param $token
     *
     * @return int
     */
    public function activateToken($token)
    {
        $this->client->select($this->dbTokenStore);
        return $this->client->hset($token, 'active', 1);
    }

    /**
     * Look up a uniq associated with the provided token.
     * @param string $token
     *
     * @return string|null
     */
    public function getUniqFromToken(string $token)
    {
        $this->client->select($this->dbTokenStore);
        if ($this->client->hexists($token, 'uniq')) {
            $uniq = $this->client->hget($token, 'uniq');
            return $uniq;
        } else {
            return null;
        }
    }

    /**
     * Returns the otp (if available) that is set on the given token, then deletes it.
     * @param $token
     *
     * @return string
     */
    public function getOTP($token)
    {
        $this->client->select($this->dbTokenStore);
        if ($this->client->hexists($token, 'otp')) {
            $otp = $this->client->hget($token, 'otp');
            return $otp;
        } else {
            return null;
        }
    }

    /**
     * Removes a saved OTP associated with a token.
     * This is useful for when an OTP was claimed.
     *
     * @param string $token
     */
    public function unlinkOTP(string $token)
    {
        $this->client->select($this->dbTokenStore);
        if ($this->client->exists($token)) {
            $this->client->hdel($token, ['otp']);
        }
    }

    /**
     * Checks if a given token is owned by a uniq.
     * @param string $uniq
     * @param string $token
     *
     * @return bool
     */
    public function ownsToken(string $uniq, string $token)
    {
        $this->client->select($this->dbTokenStore);
        if ($this->client->sismember($uniq, $token)) {
            return true;
        }
        return false;
    }

    /**
     * Assigns a token to a uniq.
     * @param string $uniq
     * @param string $token
     *
     * @return int
     */
    public function giveToken(string $uniq, string $token) : int
    {
        $this->client->select($this->dbTokenStore);
        return $this->client->sadd($uniq, [$token]);
    }

    /**
     * Unlinks a token from a uniq's ownership.
     * @param string $uniq
     * @param string $token
     *
     * @return int
     */
    public function removeToken(string $uniq, string $token) : int
    {
        $this->client->select($this->dbTokenStore);
        return $this->client->srem($uniq, $token);
    }

    /**
     * Delete the provided key'd tokens and the tokendata.
     * @param $token
     */
    public function purgeTokenKey($token)
    {
        $this->client->select($this->dbTokenStore);
        $this->client->del($token);
    }

    /**
     * Purge all owned tokens from a uniq, both historic and current.
     * This operation is O(no of tokens).
     *
     * @param $uniq
     *
     * @return int
     */
    public function purgeTokens($uniq) : int
    {
        $this->client->select($this->dbTokenStore);

        $tokens = $this->client->smembers($uniq);

        // delete any tokens (as keys) in this db, then delete the historic relations
        foreach ($tokens as $deletable) {
            $result = $this->client->del($deletable);
            Log::debug("Deleting $uniq's token: $deletable' - Result: $result");
        }

        Log::debug("Lastly, deleting $uniq's historic set'");
        return $this->client->del($uniq);
    }

    /**
     * Simply returns all tokens associated with the provided uniq.
     * @param string $uniq
     *
     * @return array
     */
    public function getAllTokens(string $uniq) : array
    {
        $all = $this->client->smembers($uniq);
        return $all;
    }

    /**
     * Internal function to increment a counter(key) in the datastore.
     * @param string $key The key to increment
     */
    private function countIncrement($key) : int
    {
        $this->client->select($this->dbCounter);
        return intval($this->client->incr($key));
    }

    /**
     * Generalise logging format for GrazerRedisService
     * @param string $specify
     */
    private function log($command, $key, $note)
    {
        Log::debug(
            sprintf( '[grazerRedis] %s [%s] %s',
                $command,
                $key,
                $note
            )
        );
    }


}
