<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Core\Router;
use App\Controllers\PingController;
use App\Controllers\AuthController;
use App\Controllers\BatalhaController;
use App\Controllers\HomeController;
use App\Controllers\ImportadorController;
use App\Controllers\LojaController;
use App\Controllers\TimeController;

// SEGURANÇA do cookie de sessão:
//   httponly -> o JavaScript não consegue ler o cookie (protege contra XSS);
//   samesite -> outro site não consegue fazer ações em nome do jogador (CSRF).
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

$router = new Router();

// utilitárias
$router->get('/api/ping', [PingController::class, 'ping']);

// páginas
$router->get('/',     [AuthController::class, 'paginaLogin']);
$router->get('/home', [HomeController::class, 'pagina']);
$router->get('/time', [TimeController::class, 'pagina']);
$router->get('/loja', [LojaController::class, 'pagina']);
$router->get('/batalha', [BatalhaController::class, 'pagina']);

// API de autenticação
$router->post('/api/login',          [AuthController::class, 'login']);
$router->post('/api/registrar',      [AuthController::class, 'registrar']);
$router->post('/api/logout',         [AuthController::class, 'logout']);
$router->get('/api/eu',              [AuthController::class, 'eu']);


// API do Team Builder
$router->get('/api/especies',        [TimeController::class, 'listarEspecies']);
$router->get('/api/time',            [TimeController::class, 'meuTime']);
$router->post('/api/time/adicionar', [TimeController::class, 'adicionar']);
$router->post('/api/time/remover',   [TimeController::class, 'remover']);
$router->post('/api/time/limpar',    [TimeController::class, 'limpar']);
$router->post('/api/time/evoluir',   [TimeController::class, 'evoluir']);
$router->post('/api/time/usar-item', [TimeController::class, 'usarItem']);
$router->post('/api/centro-pokemon', [TimeController::class, 'centroPokemon']);
$router->post('/api/time/shiny',     [TimeController::class, 'alternarShiny']);

// API do Poké Mart
$router->get('/api/loja',             [LojaController::class, 'dados']);
$router->post('/api/loja/comprar',    [LojaController::class, 'comprar']);

// API da Batalha
$router->get('/api/batalha',          [BatalhaController::class, 'estado']);
$router->post('/api/batalha/iniciar', [BatalhaController::class, 'iniciar']);
$router->post('/api/batalha/acao',    [BatalhaController::class, 'acao']);
$router->post('/api/batalha/fugir',   [BatalhaController::class, 'fugir']);

// Importação de Pokémon da PokéAPI (qualquer usuário logado)
$router->post('/api/importar', [ImportadorController::class, 'importar']);

$router->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
