<?php namespace App\Http\Controllers\v1;

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


    /**
     * Creates a new token for the uniq.
     * At this point it is not replaced or verified, just a notification and otp is generated.
     * @return Response
     */
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
                'TokenController Created',
                TokenToolkit::makeSimpleOTP()
            );

            $this->datastore->setApiAccessTokenData($newToken, $tokenVO);
            $seconds = intval(env('EXPIRE_TOKEN_DEFAULT_HOURS', 336)) * 3600;   // hours to seconds.
            $this->datastore->touchTokenTTL($newToken, $seconds);

            $this->datastore->giveToken($uniq, $newToken);

            $this->log('notifyAndSendOTP response: ' .
                strval(TokenToolkit::notifyAndSendOTP($uniq, $newToken, $user['email'])));

            return new Response('Token created', 202);
        } else if(!$uniqExists) {
            return new Response('That uniq is non-existent', 404);
        } else {
            $this->log('Something fishy during create()');
            return new Response('Something fishy going on with token request and creation...', 500);
        }
    }

    /**
     * Accepts an OTP, and verifies it against the provided (header) api token to verify.
     * If the OTP matches the token, the token is activate, the otp destroyed and all other tokens purged.
     *
     * @param string $otp
     *
     * @return Response
     */
    public function verify(string $otp)
    {
        if (!$otp) {
            return new Response('OTP Required', 400);
        }

        $token = $this->request->header('API-TOKEN');
        $stored = $this->datastore->getOTP($token);
        if (!$stored) {
            return new Response('Could not find a cached OTP this time', 404);
        }
        $uniq = $this->datastore->getUniqFromToken($token);

        if (strcmp($otp, $stored) === 0) {
            $tokens = $this->datastore->getAllTokens($uniq);
            $tokenAtKey = array_search($token, $tokens, true);
            unset($tokens[$tokenAtKey]); // keep the current request token, as it becomes the new current.

            foreach($tokens as $deletable) {
                $this->datastore->removeToken($uniq, $deletable);   // remove all but current from history set
                $this->datastore->purgeTokenKey($deletable);
            }

            // Remove the OTP fielfd from the token hash, activate it.
            $this->datastore->unlinkOTP($token);
            $this->datastore->activateToken($token);

            return new Response('Token Verified', 204);
        } else {
            return new Response('Token Mismatch', 400);
        }

    }

    /**
     * Generalise logging format
     * @param string $specify
     */
    private function log(string $specify)
    {
        Log::debug(
            sprintf( '[controller] %s [%s] %s - %s',
                $this->req->ip(),
                get_called_class(),
                __FUNCTION__,
                $specify
            )
        );

    }

}