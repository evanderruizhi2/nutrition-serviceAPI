<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('/ping', function () {
    return json_encode(['status' => 'ok']);
});
$routes->group('api', function ($routes) {
    $routes->post('login', 'Api\AuthController::login');
});

$routes->group('api', ['filter' => 'jwt'], function ($routes) {
    $routes->post('nutrition/constraints', 'Api\NutritionController::constraints');
});
