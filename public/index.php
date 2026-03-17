<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Routes\Routes;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$jwtSecret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? 'default-secret-change-me');

$app = AppFactory::create();

// Registrar todas las rutas desde el archivo centralizado
Routes::register($app, $jwtSecret);

$app->run();
