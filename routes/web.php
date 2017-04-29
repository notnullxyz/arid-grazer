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
//$app->put('/user/{uniq}', $apiVersion.'\UserController@update');  // @todo
$app->get('/user', ['middleware' => 'auth', 'uses' => $apiVersion.'\UserController@get']);
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
