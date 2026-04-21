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
$routes->post('usuario/perfil/(:num)/foto', 'AuthController::uploadProfilePhoto/$1', ['filter' => 'auth']);
$routes->put('usuario/cambiar-clave/(:num)', 'AuthController::changePassword/$1', ['filter' => 'auth']);
$routes->get('usuario/estudiantes', 'AuthController::getStudents', ['filter' => ['auth', 'admin']]);
$routes->put('usuario/estado-usuario-estudiante/(:num)', 'AuthController::toggleUserStatus/$1', ['filter' => ['auth', 'admin']]);
// Preguntas: GET público; crear/actualizar/eliminar solo admin
$routes->get('preguntas', 'PreguntaController::index');
$routes->post('preguntas', 'PreguntaController::create', ['filter' => ['auth', 'admin']]);
$routes->put('preguntas/update/(:num)', 'PreguntaController::update/$1', ['filter' => ['auth', 'admin']]);
$routes->delete('preguntas/(:num)', 'PreguntaController::delete/$1', ['filter' => ['auth', 'admin']]);
// Evidencias: GET públicos; crear/actualizar solo admin
$routes->get('evidencias', 'EvidenciasController::index');
$routes->get('evidencias/activas', 'EvidenciasController::activas');
$routes->post('evidencias', 'EvidenciasController::create', ['filter' => ['auth', 'admin']]);
$routes->put('evidencias/(:num)', 'EvidenciasController::update/$1', ['filter' => ['auth', 'admin']]);
$routes->get('evidencias/(:num)', 'EvidenciasController::show/$1');
// Noticias y Eventos: GET públicos; POST/PUT solo admin
$routes->get('noticias-eventos', 'NoticiasEventosController::index');
$routes->get('noticias-eventos/(:num)', 'NoticiasEventosController::show/$1');
$routes->post('noticias-eventos', 'NoticiasEventosController::create', ['filter' => ['auth', 'admin']]);
$routes->put('noticias-eventos/(:num)', 'NoticiasEventosController::update/$1', ['filter' => ['auth', 'admin']]);
// Cursos: GET público; crear curso (y carpeta en servidor) solo admin
$routes->get('cursos', 'CursoController::index');
$routes->get('cursos/activos-con-preguntas', 'CursoController::activosConPreguntas', ['filter' => 'auth']);
$routes->get('cursos/(:num)', 'CursoController::show/$1');
$routes->post('cursos', 'CursoController::create', ['filter' => ['auth', 'admin']]);
$routes->put('cursos/(:num)', 'CursoController::update/$1', ['filter' => ['auth', 'admin']]);
$routes->post('cursos/(:num)/portada', 'CursoController::uploadPortada/$1', ['filter' => ['auth', 'admin']]);
// Secciones de curso: listar público; crear sección (subir PDF) solo admin
$routes->get('cursos/(:num)/secciones', 'SeccionCursoController::index/$1');
$routes->get('cursos/(:num)/secciones/descargar', 'SeccionCursoController::download/$1');
$routes->post('cursos/(:num)/secciones', 'SeccionCursoController::create/$1', ['filter' => ['auth', 'admin']]);
$routes->put('cursos/(:num)/secciones/(:num)', 'SeccionCursoController::update/$1/$2', ['filter' => ['auth', 'admin']]);
$routes->post('cursos/(:num)/secciones/(:num)', 'SeccionCursoController::update/$1/$2', ['filter' => ['auth', 'admin']]);
$routes->put('cursos/(:num)/secciones/(:num)/eliminar', 'SeccionCursoController::delete/$1/$2', ['filter' => ['auth', 'admin']]);
// Preguntas sugeridas por PDF (OpenAI): solo admin
$routes->get('cursos/(:num)/preguntas-sugeridas', 'PreguntasSugeridasController::index/$1', ['filter' => ['auth', 'admin']]);
// Desarrollo del curso por estudiante: inicio y finalización (usuario autenticado)
$routes->post('cursos/(:num)/inicio', 'CursoDesarrolloController::inicio/$1', ['filter' => 'auth']);
$routes->post('cursos/(:num)/finalizacion', 'CursoDesarrolloController::finalizacion/$1', ['filter' => 'auth']);
