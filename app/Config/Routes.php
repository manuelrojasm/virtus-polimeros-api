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
// Rutas protegidas: requieren JWT (usuario autenticado)
$routes->put('usuario/perfil/(:num)', 'AuthController::updateProfile/$1', ['filter' => 'auth']);
$routes->put('usuario/cambiar-clave/(:num)', 'AuthController::changePassword/$1', ['filter' => 'auth']);
$routes->get('usuario/estudiantes', 'AuthController::getStudents', ['filter' => 'auth,admin']);
$routes->put('usuario/estado-usuario-estudiante/(:num)', 'AuthController::toggleUserStatus/$1', ['filter' => 'auth,admin']);
// Preguntas: GET público; crear/actualizar/eliminar solo admin
$routes->get('preguntas', 'PreguntaController::index');
$routes->post('preguntas', 'PreguntaController::create', ['filter' => 'auth,admin']);
$routes->put('preguntas/update/(:num)', 'PreguntaController::update/$1', ['filter' => 'auth,admin']);
$routes->delete('preguntas/(:num)', 'PreguntaController::delete/$1', ['filter' => 'auth,admin']);
// Evidencias: GET públicos; crear/actualizar solo admin
$routes->get('evidencias', 'EvidenciasController::index');
$routes->get('evidencias/activas', 'EvidenciasController::activas');
$routes->post('evidencias', 'EvidenciasController::create', ['filter' => 'auth,admin']);
$routes->put('evidencias/(:num)', 'EvidenciasController::update/$1', ['filter' => 'auth,admin']);
$routes->get('evidencias/(:num)', 'EvidenciasController::show/$1');
