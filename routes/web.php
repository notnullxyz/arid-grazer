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

$app->get('/', function () use ($app) {
    return $app->version();
});


/**
 * User
 */
$app->put('/user/{uniq}', $apiVersion.'\UserController@update');
$app->get('/user/{uniq}', $apiVersion.'\UserController@get');

/**
 * Package
 */
//$app->put('');
//$app->get();


