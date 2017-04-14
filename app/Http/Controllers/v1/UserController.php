<?php namespace App\Http\Controllers\v1;

use App\Library\Faker;
use App\Services\GrazerRedis\GrazerRedisService;
use App\Services\GrazerRedis\GrazerRedisTokenVO;
use App\Services\GrazerRedis\GrazerRedisUserVO;
use App\Services\GrazerRedis\IGrazerRedisUserVO;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Log;
use Psr\Log\InvalidArgumentException;
use function Sodium\randombytes_random16;

class UserController extends Controller
{
    private $req;

    public function __construct(Request $request, GrazerRedisService $grazerRedisService)
    {
        $this->req = $request;
        $this->datastore = $grazerRedisService;
        Log::info('UserController construction.');
    }

    public function update(string $uniq)
    {
        return new Response('Not Available', 404);
    }

    public function get(string $uniq)
    {
        Log::info('UserController/get for uniq ' . $uniq);

        $cachedUser = $this->datastore->getUser($uniq);
        return response()->json($cachedUser->get(), 200);
    }

    public function create()
    {
        Log::info('UserController/create');
        $email = $this->req->get('email');
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response('Email not accepted', 422);
        }

        if ($email && $this->datastore->emailExists($email)) {
            return new Response('Email already exists', 409);
        }

        do {
            $uniq = $this->mkUniq();
        } while ($this->datastore->exists($email, $uniq));

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

    private function assignUserToken(IGrazerRedisUserVO $userVO) {
        $NaCL = getenv('NACL') !== false ? getenv('NACL') : str_random(8);
        $token = sha1(json_encode($userVO->get()) . strval($NaCL));

        $user = $userVO->get();
        $tokenVO = new GrazerRedisTokenVO(
            $user['uniq'],
            $user['email'],
            0,
            microtime(true),
            __FUNCTION__
        );

        $this->datastore->setApiAccessTokenData($token, $tokenVO->get());
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

}