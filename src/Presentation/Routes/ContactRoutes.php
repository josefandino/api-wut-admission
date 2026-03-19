<?php

namespace App\Presentation\Routes;

use App\Middleware\JwtMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

class ContactRoutes
{
    public static function register(App $app, string $jwtSecret): void
    {
        // Rutas públicas de Contact
        $app->group('/contacts', function ($group) {
            $group->get('', \App\Application\Actions\Contact\ListContactAction::class);
            $group->post('', \App\Application\Actions\Contact\CreateContactAction::class);
            $group->get('/{id}', \App\Application\Actions\Contact\ViewContactAction::class);
        });

        // Rutas privadas de Contact
        $app->group('/admin/contacts', function ($group) {
            $group->get('', \App\Application\Actions\Contact\ListContactAction::class);
        })->add(new JwtMiddleware($jwtSecret));
    }
}
