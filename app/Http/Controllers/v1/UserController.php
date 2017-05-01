<?php namespace App\Http\Controllers\v1;

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

class UserController extends Controller
{
    private $req;
    private $datastore;

    /**
     * UserController constructor.
     *
     * @param Request            $request
     * @param GrazerRedisService $grazerRedisService
     */
    public function __construct(Request $request, GrazerRedisService $grazerRedisService)
    {
        $this->req = $request;
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
        $email = $this->req->get('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->log("FILTER_VALIDATE_EMAIL fail $email");
            return new Response('Email not accepted', 422);
        }
        
        if ($email && $this->datastore->emailExists($email)) {
            $this->log("email exists $email");
            return new Response('Email already exists', 409);
        }

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
        $token = $this->req->header('API-TOKEN');
        $realUniq = $this->datastore->getUniqFromToken($token);
        $cachedUser = $this->datastore->getUser($realUniq);
        return response()->json($cachedUser->get(), 200);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function create()
    {
        $email = $this->req->get('email');
        $user = null;
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->log("FILTER_VALIDATE_EMAIL fail $email");
            return new Response('Email not accepted', 422);
        }

        if ($email && $this->datastore->emailExists($email)) {
            $this->log("email exists $email");
            return new Response('Email already exists', 409);
        }

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
                $this->req->ip(),
                get_called_class(),
                __FUNCTION__,
                $specify
            )
        );

    }
}