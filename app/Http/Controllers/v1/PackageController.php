<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\GrazerRedis\GrazerRedisPackageVO;
use App\Services\GrazerRedis\GrazerRedisService;
use App\Services\GrazerRedis\IGrazerRedisPackageVO;
use Illuminate\Http\Request;
use Log;

class PackageController extends Controller
{

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

        // TODO Get the origin from the context of the authenticated uniq!
        $origin = 'HardCodedOrigin-999';
        // TODO Get the origin from the context of the authenticated uniq!

        // If no expiry was requested, we have no choice but to use our default.
        if (!$expire) {
            $expireSeconds = env('EXPIRE_PACKAGE_DEFAULT_HOURS', 24) * 60 * 60; // hour->sec
        } else {
            $expireSeconds = 12 * 60 * 60;  // if all else fails, we give it 12 hours to live.
        }

        if (!$this->grazerRedisService->uniqExists($dest)) {
            $this->log("non-existent uniq dest [$dest]");
            abort(410, "The uniq '$dest' is not here, and probably gone forever.");
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
        $cachedPackage = $this->grazerRedisService->getPackage($pHash);
        return response()->json($cachedPackage->get(), 200);
    }

    /**
     * This expires a package and purges it from the system.
     *
     * @param int $pId Package ID
     */
    private function expire($pId)
    {

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
        unset($pkg['sent']);    // remove time, else hash will always be unique :(
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