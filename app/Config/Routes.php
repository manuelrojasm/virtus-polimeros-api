<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->options('(:any)', 'ContactoController::options');
$routes->get('contacto', 'ContactoController::index');
$routes->post('contacto', 'ContactoController::create');
$routes->post('api/register', 'AuthController::register');
$routes->post('login', 'AuthController::login');
$routes->post('recoverPassword', 'AuthController::sendLoginReminder');
