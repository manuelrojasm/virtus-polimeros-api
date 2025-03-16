<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Home::index');
$routes->get('contacto', 'ContactoController::index');
$routes->post('contacto', 'ContactoController::create');
