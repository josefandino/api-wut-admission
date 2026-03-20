<?php

namespace App\Presentation\Routes;

use App\Middleware\JwtMiddleware;
use Slim\App;

class RateLimitRoutes
{
    public static function register(App $app, string $jwtSecret): void
    {
        // Rutas privadas para administración de rate limiting
        $app->group('/admin/rate-limits', function ($group) {
            // Ver IPs bloqueadas
            $group->get('', \App\Application\Actions\RateLimit\ListBlockedIpsAction::class);
            
            // Desbloquear una IP
            $group->post('/unblock', \App\Application\Actions\RateLimit\UnblockIpAction::class);
        })->add(new JwtMiddleware($jwtSecret));
    }
}
