<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;
use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes = Services::routes();

$routes->setDefaultNamespace('App\Controllers');
$routes->setDefaultController('Home');
$routes->setDefaultMethod('index');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

/*
|--------------------------------------------------------------------------
| API Pública – Fila Cirúrgica
|--------------------------------------------------------------------------
*/

$routes->group('api/public', ['filter' => 'ratelimit'], function ($routes) {
    $routes->post('fila/token', 'FilaPublicaController::token');
    $routes->get('fila/consulta', 'FilaPublicaController::consulta');
});

//use CodeIgniter\Environment;

if (ENVIRONMENT !== 'production') {
    $routes->get('swagger', function () {
        return redirect()->to('/swagger/index.html');
    });
}

