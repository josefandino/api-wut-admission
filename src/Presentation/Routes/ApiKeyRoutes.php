<?php

namespace App\Presentation\Routes;

use App\Middleware\JwtMiddleware;
use Slim\App;

class ApiKeyRoutes
{
    public static function register(App $app, string $jwtSecret): void
    {
        // Rutas privadas para administración de API Keys
        $app->group('/admin/api-keys', function ($group) {
            // Listar API Keys
            $group->get('', \App\Application\Actions\ApiKey\ListApiKeysAction::class);
            
            // Crear nueva API Key
            $group->post('', \App\Application\Actions\ApiKey\CreateApiKeyAction::class);
        })->add(new JwtMiddleware($jwtSecret));

        // Rutas privadas para análisis de fraude
        $app->group('/admin/fraud', function ($group) {
            // Ver analytics de fraude
            $group->get('/analytics', \App\Application\Actions\Fraud\FraudAnalyticsAction::class);
        })->add(new JwtMiddleware($jwtSecret));
    }
}
