<?php

namespace App\Admission\Routes;

use Slim\Routing\RouteCollectorProxy;
use App\Admission\Controllers\AdmissionController;
use App\Middleware\JwtMiddleware;

class AdmissionRoutes
{
    public static function register(RouteCollectorProxy $app, string $jwtSecret): void
    {
        $controller = new AdmissionController();

        $app->get('', function ($request, $response) use ($controller) {
            return $controller->index($request, $response);
        });

        $app->post('', function ($request, $response) use ($controller) {
            return $controller->create($request, $response);
        });

        $app->get('/{id}', function ($request, $response, $args) use ($controller) {
            return $controller->show($request, $response, $args);
        });
    }

    public static function registerAdmin(RouteCollectorProxy $app, string $jwtSecret): void
    {
        $controller = new AdmissionController();

        $app->get('', function ($request, $response) use ($controller) {
            return $controller->listAll($request, $response);
        })->add(new JwtMiddleware($jwtSecret));
    }
}
