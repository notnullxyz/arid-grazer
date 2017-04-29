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
        // Do not skip auth here ;)
        return new Response('Not Available', 404);
    }

    /**
     * Get a user by it's uniq, only allowed to get the uniq you're authenticated for.
     * @param string $uniq
     *
     * @return
     */
    public function get(string $uniq)
    {
        $realUniq = $this->datastore->getUniqFromToken($this->req->header('API-TOKEN'));

        if (strcmp($realUniq, $uniq) === 0) {
            $cachedUser = $this->datastore->getUser($uniq);
            return response()->json($cachedUser->get(), 200);
        }

        return new Response('You are not entitled to snooping', 403);
    }

    /**
     * @return \Illuminate\Http\JsonResponse|Response
     */
    public function create()
    {
        $email = $this->req->get('email');
        $token = $user = null;
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

        } catch (InvalidArgumentException $iae) {
            return new Response('Provided parameters were not acceptable', 422);
        } catch (\Exception $e) {
            return new Response('Some unhandled exception occurred in create: ' . $e->getMessage(), 500);
        } finally {
            $userResponse = $user->get();
            $userResponse['token'] = $token;
            return response()->json($userResponse, 200);
        }
    }

    /**
     * @todo  : this should be calling TokenController:create
     */
    private function assignUserToken(IGrazerRedisUserVO $userVO) {
        $token = TokenToolkit::makeToken($userVO->get());

        $user = $userVO->get();
        $tokenVO = new GrazerRedisTokenVO(
            $user['uniq'],
            $user['email'],
            0,
            microtime(true),
            get_called_class() . '::'. __FUNCTION__,
            TokenToolkit::makeSimpleOTP()
        );

        $this->datastore->setApiAccessTokenData($token, $tokenVO);
        $seconds = intval(env('EXPIRE_TOKEN_DEFAULT_HOURS', 336)) * 3600;   // hours to seconds.
        $this->datastore->touchTokenTTL($token, $seconds);
        $this->datastore->giveToken($user['uniq'], $token);

        TokenToolkit::notifyAndSendOTP($user['uniq'], $token);

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