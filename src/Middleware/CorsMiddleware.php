<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Middleware CORS - Solo permite peticiones desde orígenes autorizados
 */
class CorsMiddleware implements MiddlewareInterface
{
    private const ALLOWED_ORIGINS = [
        'https://worldtheologyuniversityedu.com',
        'https://www.worldtheologyuniversityedu.com',
        'http://localhost:6300',
    ];

    public function process(Request $request, RequestHandler $handler): Response
    {
        $origin = $request->getHeaderLine('Origin');

        // Preflight OPTIONS -> responder inmediatamente
        if ($request->getMethod() === 'OPTIONS') {
            return $this->createPreflightResponse($origin);
        }

        // Sin Origin o Origin no permitido -> bloquear
        if (empty($origin) || !in_array($origin, self::ALLOWED_ORIGINS, true)) {
            $response = new SlimResponse(403);
            $response->getBody()->write(json_encode([
                'status' => false,
                'error' => 'Origen no autorizado'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Origin válido -> continuar y agregar headers CORS a la respuesta
        $response = $handler->handle($request);

        return $response
            ->withHeader('Access-Control-Allow-Origin', $origin)
            ->withHeader('Access-Control-Allow-Credentials', 'true')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key')
            ->withHeader('Access-Control-Max-Age', '86400');
    }

    private function createPreflightResponse(string $origin): Response
    {
        $response = new SlimResponse(200);

        if (!empty($origin) && in_array($origin, self::ALLOWED_ORIGINS, true)) {
            return $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
                ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-API-Key')
                ->withHeader('Access-Control-Max-Age', '86400');
        }

        // Preflight de origen no permitido
        return $response;
    }
}
