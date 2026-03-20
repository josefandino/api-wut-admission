<?php

namespace App\Middleware;

use App\Shared\Sanitizer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Middleware que valida las solicitudes entrantes contra patrones maliciosos
 */
class RequestValidationMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        // Obtener información del cliente
        $clientInfo = Sanitizer::getClientInfo();

        // Validar método HTTP
        $method = $request->getMethod();
        if (!in_array($method, ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS', 'HEAD'])) {
            error_log("Invalid HTTP method: {$method} - " . json_encode($clientInfo));
            return $this->createErrorResponse('Invalid request method', 400);
        }

        // Validar tamaño de la solicitud
        $contentLength = $request->getHeaderLine('Content-Length');
        if ($contentLength && (int)$contentLength > 10485760) { // 10MB
            error_log("Request size exceeds limit: {$contentLength} - " . json_encode($clientInfo));
            return $this->createErrorResponse('Request body too large', 413);
        }

        // Validar Content-Type si es POST/PUT/PATCH
        if (in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $contentType = $request->getHeaderLine('Content-Type');
            
            // Solo permitir JSON o form-data
            if ($contentType && 
                strpos($contentType, 'application/json') === false && 
                strpos($contentType, 'application/x-www-form-urlencoded') === false &&
                strpos($contentType, 'multipart/form-data') === false) {
                error_log("Invalid Content-Type: {$contentType} - " . json_encode($clientInfo));
                return $this->createErrorResponse('Invalid Content-Type', 415);
            }
        }

        // Validar URI contra patrones maliciosos
        $uri = (string)$request->getUri();
        if (!Sanitizer::isSafe($uri)) {
            error_log("Malicious URI detected: {$uri} - " . json_encode($clientInfo));
            return $this->createErrorResponse('Invalid request', 400);
        }

        // Validar headers sospechosos
        if ($this->hasSuspiciousHeaders($request)) {
            error_log("Suspicious headers detected - " . json_encode($clientInfo));
            return $this->createErrorResponse('Invalid request headers', 400);
        }

        // Obtener y validar body si existe
        $body = (string)$request->getBody();
        if ($body && !empty($body)) {
            // Validar que no contenga patrones maliciosos
            if (!Sanitizer::isSafe($body)) {
                error_log("Malicious request body detected - " . json_encode($clientInfo));
                return $this->createErrorResponse('Invalid request data', 400);
            }
        }

        // Registrar solicitudes sospechosas
        if ($this->isSuspiciousRequest($request)) {
            error_log("Suspicious request detected - Method: {$method}, URI: {$uri} - " . json_encode($clientInfo));
        }

        return $handler->handle($request);
    }

    /**
     * Detecta headers sospechosos
     */
    private function hasSuspiciousHeaders(Request $request): bool
    {
        $suspiciousPatterns = [
            'X-Original-URL',
            'X-Rewrite-URL',
            'X-HTTP-Method-Override',
        ];

        foreach ($suspiciousPatterns as $header) {
            if ($request->hasHeader($header)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detecta solicitudes sospechosas
     */
    private function isSuspiciousRequest(Request $request): bool
    {
        $uri = strtolower((string)$request->getUri());

        // Patrones de ataque comunes
        $suspiciousPatterns = [
            'union select',
            'drop table',
            'exec(',
            'eval(',
            '../',
            '..\\',
            'cmd.exe',
            '/etc/passwd',
            'base64_decode',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($uri, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Crea una respuesta de error
     */
    private function createErrorResponse(string $message, int $statusCode): Response
    {
        $response = new SlimResponse($statusCode);
        $response->getBody()->write(json_encode([
            'exito' => false,
            'error' => $message
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
