<?php
/**
 * routes-v1.php
 * Part of arid-grazer
 *
 * @author: Marlon
 *
 */


/**
 * User
 */
$app->put('/user/{uniq}', 'v2\UserController@update');
$app->get('/user/{uniq}', 'v2\UserController@get');


/**
 * Package
 */

//$app->put('');
//$app->get();
