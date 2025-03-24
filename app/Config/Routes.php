<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->post('api/register', 'AuthController::register');
$routes->post('login', 'AuthController::login');
$routes->post('recoverPassword', 'AuthController::sendLoginReminder');