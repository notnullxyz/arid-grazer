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

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\GrazerRedis\GrazerRedisPackageVO;
use App\Services\GrazerRedis\GrazerRedisService;
use App\Services\GrazerRedis\IGrazerRedisPackageVO;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Log;

class PackageController extends Controller
{

    const HOUR = 60*60;
    private $request;
    private $grazerRedisService;

    public function __construct(Request $request, GrazerRedisService $grazerRedisService, UserController $user)
    {
        $this->request = $request;
        $this->grazerRedisService = $grazerRedisService;
        $this->user = $user;
    }

    public function create()
    {
        $this->validate($this->request, [
            'dest' => 'required|between:4,128', // 128 a sane max for a generated uniq?
            'label' => 'required|string|between:6,255',
            'expire' => 'date|after:today',
            'content' => 'required|string'
        ]);

        $dest = $this->request->get('dest');
        $label = $this->request->get('label');
        $expire = $this->request->get('expire');
        $content = $this->request->get('content');

        $origin = (string)$this->grazerRedisService->getUniqFromToken($this->request->header('API-TOKEN'));

        if (!$this->grazerRedisService->uniqExists($dest)) {
            $this->log("non-existent uniq dest [$dest]");
            abort(410, "The uniq '$dest' is not here, and probably gone forever.");
        }

        // If expiry not requested as part of package meta, fallback to our own.
        if (!$expire) {
            $expireSeconds = env('EXPIRE_PACKAGE_DEFAULT_HOURS', 24) * static::HOUR;    // hour -> sec
        } else {
            $expireSeconds = 12 * static::HOUR; // This means 12 hours
        }

        $packageVO = new GrazerRedisPackageVO($origin, $dest, $label, microtime(true), $expireSeconds,
            $content);

        $VOHash = $this->mkPkgHash($packageVO);

        // fend off duplicates
        if (!$this->grazerRedisService->packageExists($VOHash)) {
            $this->grazerRedisService->setPackage($packageVO, $VOHash);
        } else {
            $this->log("duplicate package hash [$VOHash]");

            abort(409,
                "A package with this exact hash, has already been inserted in the system -" . $VOHash);
        }

        // Augment the response with the ID included. Useful for clients.
        $packageResponse = $packageVO->get();
        $packageResponse['id'] = $VOHash;

        return response()->json($packageResponse, 200);
    }

    /**
     * Retrieves a package from the system by its hash.
     *
     * @param int $pId Package hash
     */
    public function get($pHash)
    {
        $receiverUniq = $this->grazerRedisService->getUniqFromToken($this->request->header('API-TOKEN'));

        // compare the owner(recipient of the stored package at this hash, with the requesting token's uniq)
        if (strcmp($this->grazerRedisService->getPackageRecipient($pHash), $receiverUniq) === 0) {
            $cachedPackage = $this->grazerRedisService->getPackage($pHash);
            return response()->json($cachedPackage->get(), 200);
        } else {
            return new Response('Forbidden (resource does not belong to you)', 403);
        }
    }

    /**
     * Generate a hash for a package, for identity or other uses.
     * @param IGrazerRedisPackageVO $pkgVO
     *
     * @return string
     */
    private function mkPkgHash(IGrazerRedisPackageVO $pkgVO) : string
    {
        $pkg = $pkgVO->get();
        unset($pkg['sent']);    // remove time field, or this hash will just always be unique.
        return md5(json_encode($pkg));
    }

    /**
     * Generalise logging format
     * @param string $specify
     */
    private function log(string $specify)
    {
        Log::debug(
            sprintf( '[controller] %s [%s] %s - %s',
                $this->request->ip(),
                get_called_class(),
                __FUNCTION__,
                $specify
            )
        );
    }
}