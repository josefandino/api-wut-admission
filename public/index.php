<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\Admission\Routes\AdmissionRoutes;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$app = AppFactory::create();

$jwtSecret = $_ENV['JWT_SECRET'] ?? $_ENV['JWT_SECRET'] ?? 'default-secret-change-me';

$app->group('/admissions', function ($group) use ($jwtSecret) {
    AdmissionRoutes::register($group, $jwtSecret);
});

$app->group('/admin/admissions', function ($group) use ($jwtSecret) {
    AdmissionRoutes::registerAdmin($group, $jwtSecret);
});

$app->group('/registration', function ($group) {
    $group->get('', function ($request, $response) {
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Registration endpoint'
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    });
    
    $group->post('', function ($request, $response) {
        $response->getBody()->write(json_encode([
            'success' => true,
            'message' => 'Registration created'
        ]));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    });
});

$app->run();
