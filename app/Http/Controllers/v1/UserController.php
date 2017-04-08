<?php

namespace App\Http\Controllers\v1;

use App\Library\Faker;
use App\Services\GrazerRedis\GrazerRedisService;
use App\Services\GrazerRedis\GrazerRedisUserVO;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\Response;
use Log;
use Psr\Log\InvalidArgumentException;

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

    public function get(string $email)
    {
        Log::info('UserController/get for email ' . $email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response('Email not accepted', 422);
        }


        print "going to try get '$email' now";
        $cachedUser = $this->datastore->getUser($email);
        print "got it";
        dd($cachedUser);

    }

    public function create()
    {
        Log::info('UserController/create');
        $faker = new Faker();
        $email = $this->req->get('email');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return new Response('Email not accepted', 422);
        }

        if ($email && $this->datastore->emailExists($email)) {
            return new Response('Email already exists', 409);
        }

        do {
            $pre = strtolower($faker->randomPre());
            $post = strtolower($faker->randomPost());
            $uniq = "$pre-$post";
        } while ($this->datastore->exists($email, $uniq));

        try {
            $user = new GrazerRedisUserVO($uniq, $email, true, $this->created = microtime(true));
            $this->datastore->setUser($user);
        } catch (InvalidArgumentException $iae) {
            return new Response('Provided parameters were not acceptable', 422);
        } catch (\Exception $e) {
            return new Response('Client error occurred', 400);
        }

        return new Response('User Created', 201);
    }
}