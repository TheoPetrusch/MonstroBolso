<?php
declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Controllers\PingController;

$router = new Router();

$router->get('/api/ping', [PingController::class, 'ping']);
$router->get('/api/db',   [PingController::class, 'db']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);