<?php

namespace App\Presentation\Routes;

use Slim\App;
use App\Middleware\JwtMiddleware;

class AdmissionRoutes
{
    public static function register(App $app, string $jwtSecret): void
    {
        // Rutas públicas de Admission
        $app->group('/admissions', function ($group) {
            $group->get('', \App\Application\Actions\Admission\ListAdmissionsAction::class);
            $group->post('', \App\Application\Actions\Admission\CreateAdmissionAction::class);
            $group->get('/{id}', \App\Application\Actions\Admission\ViewAdmissionAction::class);
        });

        // Rutas privadas de Admission
        $app->group('/admin/admissions', function ($group) {
            $group->get('', \App\Application\Actions\Admission\ListAdmissionsAction::class);
        })->add(new JwtMiddleware($jwtSecret));

        // Rutas de Registration
        $app->group('/registration', function ($group) {
            $group->get('', function ($request, $response) {
                $response->getBody()->write(json_encode([
                    'exito' => true,
                    'mensaje' => 'Registration endpoint'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            });
            
            $group->post('', function ($request, $response) {
                $response->getBody()->write(json_encode([
                    'exito' => true,
                    'mensaje' => 'Registration created'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
            });
        });
    }
}
