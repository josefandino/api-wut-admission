<?php

namespace App\Routes;

use Slim\App;
use App\Presentation\Routes\AdmissionRoutes;

class Routes
{
    public static function register(App $app, string $jwtSecret): void
    {
        AdmissionRoutes::register($app, $jwtSecret);
    }
}
