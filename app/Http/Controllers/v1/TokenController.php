<?php namespace App\Http\Controllers\v1;

use App\Http\Controllers\Controller;
use App\Library\TokenToolkit;
use App\Services\GrazerRedis\GrazerRedisService;
use App\Services\GrazerRedis\GrazerRedisTokenVO;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
        $uniqExists = $this->datastore->uniqExists($uniq);

        if ($uniqExists) {
            $user = $this->datastore->getUser($uniq)->get();
            $newToken = TokenToolkit::makeToken($user);

            $tokenVO = new GrazerRedisTokenVO(
                $uniq,
                $user['email'],
                0,
                microtime(true),
                'TokenController Created'
            );

            $this->datastore->setApiAccessTokenData($newToken, $tokenVO);
            $seconds = intval(env('EXPIRE_TOKEN_DEFAULT_HOURS', 336)) * 3600;   // hours to seconds.
            $this->datastore->touchTokenTTL($newToken, $seconds);

            // expire previous tokens for this uniq @todo - how the fek


            TokenToolkit::notifyAndSendOTP($uniq, $newToken);
            return new Response('Token created', 202);
        } else if(!$uniqExists) {
            return new Response('That uniq is non-existent', 404);
        } else {
            // @todo - log this - it should not occur.
            return new Response('Something fishy going on with token request and creation...', 500);
        }
    }

    public function verify()
    {

    }

}