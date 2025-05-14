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
$routes->put('usuario/perfil/(:num)', 'AuthController::updateProfile/$1');
$routes->put('usuario/cambiar-clave/(:num)', 'AuthController::changePassword/$1');
$routes->get('usuario/estudiantes', 'AuthController::getStudents');
$routes->put('usuario/estado-usuario-estudiante/(:num)', 'AuthController::toggleUserStatus/$1');
$routes->get('preguntas', 'PreguntaController::index');
$routes->post('preguntas', 'PreguntaController::create');
$routes->put('preguntas/update/(:num)', 'PreguntaController::update/$1');
$routes->delete('preguntas/(:num)', 'PreguntaController::delete/$1');
