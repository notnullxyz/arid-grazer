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

use App\Library\Faker;
use App\Library\TokenToolkit;
use App\Services\GrazerRedis\GrazerRedisService;
use App\Services\GrazerRedis\GrazerRedisTokenVO;
use App\Services\GrazerRedis\GrazerRedisUserVO;
use App\Services\GrazerRedis\IGrazerRedisUserVO;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Psr\Log\InvalidArgumentException;
use Illuminate\Support\Facades\Cache;

class UserController extends Controller
{
    private $request;
    private $datastore;

    /**
     * UserController constructor.
     *
     * @param Request            $request
     * @param GrazerRedisService $grazerRedisService
     */
    public function __construct(Request $request, GrazerRedisService $grazerRedisService)
    {
        $this->request = $request;
        $this->datastore = $grazerRedisService;
    }

    /**
     * @param string $uniq
     *
     * @return Response
     */
    public function update(string $uniq)
    {

        $user = $this->datastore->getUser($uniq);
        $this->validate($this->request, ['email' => 'bail|required|email|email_unique']);
        $email = $this->request->get('email');
        $updateUser = $this->datastore->updateUser($user, $email);

        return response()->json($updateUser->get(), 200);
    }

    /**
     * Get user data - only allowed to get the uniq you're authenticated for.
     *
     * @return
     */
    public function get()
    {
        $token = $this->request->header('API-TOKEN');
        $realUniq = $this->datastore->getUniqFromToken($token);
        $cachedUser = $this->datastore->getUser($realUniq);
        return response()->json($cachedUser->get(), 200);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function create()
    {
        $this->validate($this->request, ['email' => 'bail|required|email|email_unique']);

        $email = $this->request->get('email');
        $user = null;

        do {
            $uniq = $this->mkUniq();
        } while ($this->datastore->uniqExists($uniq));

        try {
            $user = new GrazerRedisUserVO($uniq, $email, true, microtime(true));
            $this->datastore->setUser($user);
            $token = $this->assignUserToken($user);
            $userResponse = $user->get();
            $userResponse['token'] = $token;
            return response()->json($userResponse, 200);

        } catch (InvalidArgumentException $iae) {
            return new Response('Provided parameters were not acceptable', 422);
        } catch (\Exception $e) {
            return new Response('Some unhandled exception occurred in create: ' . $e->getMessage(), 500);
        }
    }

    /**
     * @todo  : this should be calling TokenController:create
     */
    private function assignUserToken(IGrazerRedisUserVO $userVO) {
        $token = TokenToolkit::makeToken($userVO->get());

        $user = $userVO->get();
        $otp = TokenToolkit::makeSimpleOTP();
        $tokenVO = new GrazerRedisTokenVO(
            $user['uniq'],
            $user['email'],
            0,
            microtime(true),
            get_called_class() . '::'. __FUNCTION__,
            $otp
        );

        $this->datastore->setApiAccessTokenData($token, $tokenVO);
        $seconds = intval(env('EXPIRE_TOKEN_DEFAULT_HOURS', 336)) * 3600;   // hours to seconds.
        $this->datastore->touchTokenTTL($token, $seconds);
        $this->datastore->giveToken($user['uniq'], $token);

        try {

            // useful for development, so debug only.
            Log::Debug("Hello Developer: Assigned token '$token' and otp '$otp' to user " . $user['uniq']);

            // Catching issues happening during mailing, no need to bring the whole process to a standstill.
            TokenToolkit::notifyAndSendOTP($user['uniq'], $token, $user['email'], $otp);
        } catch (\ErrorException $e) {
            Log::warning('TokenToolkit::notifyAndSendOTP had a panic attack: ' . $e->getMessage());
        }

        return $token;
    }

    /**
     * Create a uniq and simply return it.
     * @return string
     */
    private function mkUniq() : string {
        $faker = new Faker();
        $pre = strtolower($faker->randomPre());
        $post = strtolower($faker->randomPost());
        return "$pre-$post";
    }


    /**
     * Generalise logging format - Debugging only for dev.
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