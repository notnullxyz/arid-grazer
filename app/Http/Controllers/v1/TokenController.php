<?php namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Services\GrazerRedis\GrazerRedisService;
use Illuminate\Http\Request;

/**
 * TokenController.php
 * Part of arid-grazer
 *
 * @author: Marlon
 *
 */


class TokenController extends Controller
{
    private $request;
    private $datastore;

    /**
     * TokenController constructor.
     *
     * @param Request            $request
     * @param GrazerRedisService $grazerRedisService
     */
    public function __construct(Request $request, GrazerRedisService $grazerRedisService)
    {
        $this->datastore = $grazerRedisService;
        $this->request = $request;
    }


    public function create()
    {
        $this->validate($this->request,
            [
               'uniq'   =>  'required|string'
            ]);
        $uniq = strval($this->request->get('uniq'));

        if ($this->datastore->exists($uniq)) {

        }

    }

    public function verify()
    {

    }

}