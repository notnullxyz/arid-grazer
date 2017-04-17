<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

use App\Library\ApiVersionTool;

$apiVersion = 'v' . ApiVersionTool::validateAndGetApiVersionFromHeader();

$requestId = uniqid('req_', true);

$app->get('/', function () use ($app) {
    return $app->version();
});

/**
 * User
 */
//$app->put('/user/{uniq}', $apiVersion.'\UserController@update');  // @todo
$app->get('/user/{email}', ['middleware' => 'auth', 'uses' => $apiVersion.'\UserController@get', 'gom' => $requestId]);
$app->post('/user', $apiVersion.'\UserController@create');


/**
 * Package
 */
$app->post('/package', ['middleware' => 'auth', 'uses' => $apiVersion.'\PackageController@create']);
$app->get('/package/{id}', ['middleware' => 'auth', 'uses' => $apiVersion.'\PackageController@get']);


/**
 * Token
 */
$app->post('/token', $apiVersion.'\TokenController@create');
$app->get('/token/{otp}', $apiVersion.'\TokenController@verify');


// @todo temp notes for token endpoints.

// /token - request a new token, supplying a uniq. This creates a token, in a disabled state, and sends an email or
//          some message to the uniq containing a once-off-code with a limit ttl. respond with 201 created or 404

// /verify - once a uniq received his once-off-code, this endpoint will verify it, and enable the token.
//            it will be neccesary to now use the newly generated token and otp to validate and enable.
