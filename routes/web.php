<?php

/** @var \Laravel\Lumen\Routing\Router $router */

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

$router->get('/', function () use ($router) {
    return $router->app->version();
});

$router->group(['prefix' => 'blog'], function () use ($router) {
    $router->get('/weather', 'Controller@weather');
    $router->get('/getMsgList', 'WebController@getList');
    
    $router->post('/addMsg', 'WebController@addMsg');

    $router->delete('/delMsg/{msg_ids}', 'WebController@delMsg');
});

// $router->group(['prefix' => 'blog', 'middleware' => 'admin'], function () use ($router) {
//     $router->get('/mine', 'AccountController@mine');
// });
