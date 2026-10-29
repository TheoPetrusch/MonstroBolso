<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Controllers\PingController;
use App\Controllers\AuthController;

session_start();

$router = new Router();

// utilitárias
$router->get('/api/ping', [PingController::class, 'ping']);
$router->get('/api/db',   [PingController::class, 'db']);

// páginas
$router->get('/',     [AuthController::class, 'paginaLogin']);
$router->get('/home', [AuthController::class, 'paginaHome']);

// API de autenticação
$router->post('/api/login',          [AuthController::class, 'login']);
$router->post('/api/registrar',      [AuthController::class, 'registrar']);
$router->post('/api/logout',         [AuthController::class, 'logout']);
$router->get('/api/eu',              [AuthController::class, 'eu']);
$router->post('/api/perfil/trocar',  [AuthController::class, 'trocarPerfil']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
