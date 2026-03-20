<?php

/**
 * cPanel Entry Point - Subdominio: system
 *
 * Sube este archivo a:       public_html/system/index.php
 * Sube el proyecto a:        home/tu-usuario/api-wut-admission/
 *
 * Si la ruta del proyecto es diferente, ajusta APP_PATH.
 */

define('APP_PATH', dirname(dirname(__DIR__)) . '/api-wut-admission');

require_once APP_PATH . '/vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Routes\Routes;
use App\Middleware\ApiKeyMiddleware;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(APP_PATH);
$dotenv->load();

$jwtSecret = getenv('JWT_SECRET') ?: ($_ENV['JWT_SECRET'] ?? 'default-secret-change-me');

$app = AppFactory::create();

$app->add(new ApiKeyMiddleware());

// Error middleware - muestra detalles en logs (no en respuesta en producción)
// $errorMiddleware = $app->addErrorMiddleware(false, true, true);

Routes::register($app, $jwtSecret);

$app->run();
