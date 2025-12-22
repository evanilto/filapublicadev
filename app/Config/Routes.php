<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->group('api/public', ['filter' => 'ratelimit'], function($routes) {
    $routes->post('fila/token', 'FilaPublicaController::token');
    $routes->get('fila/consulta', 'FilaPublicaController::consulta');
});

