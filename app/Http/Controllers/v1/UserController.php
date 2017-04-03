<?php

namespace App\Http\Controllers\v1;

use Log;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class UserController extends Controller
{
    private $req;

    public function __construct(Request $request)
    {
        $this->req = $request;
        Log::info('UserController construction.');
    }

    public function update(string $uniq)
    {
        var_dump($this->req->json()->all());

        Log::info('UserController/update for uniq ' . $uniq);

        return "PUT /user/" . $uniq;
    }

    public function get(string $uniq)
    {
        Log::info('UserController/get for uniq ' . $uniq);

        return "GET v1 /user/" . $uniq;
    }
}