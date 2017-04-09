<?php

namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\GrazerRedis\GrazerRedisService;
use Illuminate\Http\Request;
use Log;

class PackageController extends Controller
{

    public function __construct(Request $request, GrazerRedisService $grazerRedisService, UserController $user)
    {
        Log::info('PackageController construction.');
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
            'content' => 'required'
        ]);

        $dest = $this->request->get('dest');
        $label = $this->request->get('label');
        $expire = $this->request->get('expire');
        $content = $this->request->get('content');

        // basic checks to help curb duplication.

    }

    /**
     * Retrieves a package from the system by its ID.
     *
     * @param int $pId Package ID
     */
    public function get($pId)
    {

    }

    /**
     * This expires a package and purges it from the system.
     *
     * @param int $pId Package ID
     */
    private function expire($pId)
    {

    }
}