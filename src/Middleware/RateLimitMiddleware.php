<?php

namespace App\Middleware;

use App\Shared\RateLimiter;
use App\Shared\Sanitizer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Middleware de Rate Limiting PRO
 * 
 * Control inteligente de solicitudes por IP:
 * - 1-2 peticiones: Permitidas
 * - 3+ peticiones en 1 minuto: BLOQUEADA 24 horas
 */
class RateLimitMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Obtener IP del cliente
        $ip = Sanitizer::getClientIp();
        
        // Obtener endpoint y método
        $endpoint = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Verificar rate limit
        $result = RateLimiter::checkRateLimit($ip, $endpoint, $method);

        // Si no es permitido, retornar error
        if (!$result['allowed']) {
            return $this->createRateLimitResponse(
                $result['message'],
                $result['retry_after'],
                429
            );
        }

        // Procesar solicitud
        $response = $handler->handle($request);

        // Agregar headers informativos de rate limit
        $response = $response->withHeader('X-RateLimit-Message', $result['message']);
        
        // Si hay retry_after, agregarlo
        if ($result['retry_after'] > 0) {
            $response = $response->withHeader('Retry-After', (string)$result['retry_after']);
        }

        return $response;
    }

    /**
     * Crea una respuesta de rate limit excedido
     */
    private function createRateLimitResponse(string $message, int $retryAfter, int $statusCode): Response
    {
        $response = new SlimResponse($statusCode);
        
        $payload = [
            'exito' => false,
            'error' => $message,
            'code' => 'RATE_LIMIT_EXCEEDED',
            'retry_after' => $retryAfter,
        ];

        // Convertir retry_after a formato legible si es mayor a 3600 segundos
        if ($retryAfter > 3600) {
            $hours = floor($retryAfter / 3600);
            $payload['retry_after_readable'] = "{$hours} horas";
        } else {
            $minutes = ceil($retryAfter / 60);
            $payload['retry_after_readable'] = "{$minutes} minutos";
        }

        $response->getBody()->write(json_encode($payload));
        
        // Headers de rate limit
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('X-RateLimit-Limit', '3')
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string)(time() + $retryAfter))
            ->withHeader('Retry-After', (string)$retryAfter);
    }
}
