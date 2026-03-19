<?php

namespace App\Routes;

use Slim\App;
use App\Presentation\Routes\AdmissionRoutes;
use App\Presentation\Routes\ContactRoutes;
use App\Presentation\Routes\RateLimitRoutes;
use App\Presentation\Routes\ApiKeyRoutes;

class Routes
{
    public static function register(App $app, string $jwtSecret): void
    {
        AdmissionRoutes::register($app, $jwtSecret);
        ContactRoutes::register($app, $jwtSecret);
        RateLimitRoutes::register($app, $jwtSecret);
        ApiKeyRoutes::register($app, $jwtSecret);
    }
}
