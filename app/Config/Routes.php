<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->group('api', function ($routes) {
    $routes->post('login', 'Api\AuthController::login');
});

$routes->group('api', ['filter' => 'jwt'], function ($routes) {
    $routes->post('nutrition/constraints', 'Api\NutritionController::constraints');
});
