<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Routes\Routes;
use App\Middleware\ApiKeyMiddleware;
use App\Middleware\CorsMiddleware;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$jwtSecret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? 'default-secret-change-me');

$app = AppFactory::create();

// Middleware global - X-API-Key requerido en TODAS las peticiones
$app->add(new ApiKeyMiddleware());

// CORS - Solo permite peticiones desde worldtheologyuniversityedu.com
// Se agrega después del ApiKey para que se ejecute ANTES (Slim invierte el orden)
$app->add(new CorsMiddleware());

// Registrar todas las rutas desde el archivo centralizado
Routes::register($app, $jwtSecret);

$app->run();
