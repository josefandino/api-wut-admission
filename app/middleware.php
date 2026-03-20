<?php

declare(strict_types=1);

use App\Middleware\SecurityHeadersMiddleware;
use App\Middleware\RequestValidationMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\ApiKeyMiddleware;
use Slim\App;

return function (App $app) {
    // Orden importante: el middleware se ejecuta en orden inverso al agregarse
    // Últimos en agregarse = Primeros en ejecutarse
    
    // Middleware de headers de seguridad (último en ejecutarse)
    $app->add(SecurityHeadersMiddleware::class);
    
    // Middleware de validación de API Key
    $app->add(ApiKeyMiddleware::class);
    
    // Middleware de rate limiting
    $app->add(RateLimitMiddleware::class);
    
    // Middleware de validación de solicitudes (primero en ejecutarse)
    $app->add(RequestValidationMiddleware::class);
};
