<?php

namespace App\Middleware;

use App\Shared\Database;
use App\Shared\Sanitizer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Middleware de Validación de API Key
 * 
 * TODAS las peticiones sin excepción requieren X-API-Key válida.
 * Sin X-API-Key válida = no hay acceso, no hay información.
 */
class ApiKeyMiddleware implements MiddlewareInterface
{

         // Endpoints que NO requieren API Key (públicos)
    // const PUBLIC_ENDPOINTS = [
    //     '/auth/login',
    // ];

    const ERROR_MESSAGE = 'Estamos presentando problemas en el momento, intenta más tarde y contacta a soporte.';

    public function process(Request $request, RequestHandler $handler): Response
    {
        $endpoint = $request->getUri()->getPath();
        $method = $request->getMethod();
        $apiKey = $request->getHeaderLine('X-API-Key');

        // Sin X-API-Key -> bloqueado
        if (empty($apiKey)) {
            error_log("BLOCKED - Sin X-API-Key - IP: " . Sanitizer::getClientIp() . " - {$method} {$endpoint}");
            return $this->blocked();
        }

        // Validar contra BD
        $keyData = $this->validateApiKey($apiKey);

        if (!$keyData || !$keyData['is_active']) {
            error_log("BLOCKED - API Key inválida o inactiva - IP: " . Sanitizer::getClientIp() . " - {$method} {$endpoint}");
            return $this->blocked();
        }

        // Expirada
        if ($keyData['expires_at']) {
            if (new \DateTime() > new \DateTime($keyData['expires_at'])) {
                error_log("BLOCKED - API Key expirada - Key: {$keyData['key_name']} - IP: " . Sanitizer::getClientIp());
                return $this->blocked();
            }
        }

        $clientIp = Sanitizer::getClientIp();

        // IP no permitida o bloqueada
        if (!$this->isIpAllowed($keyData, $clientIp) || $this->isIpBlocked($keyData, $clientIp)) {
            error_log("BLOCKED - IP restringida - Key: {$keyData['key_name']} - IP: {$clientIp}");
            return $this->blocked();
        }

        // Endpoint no permitido
        if (!$this->isEndpointAllowed($keyData, $endpoint)) {
            error_log("BLOCKED - Endpoint no permitido - Key: {$keyData['key_name']} - {$method} {$endpoint}");
            return $this->blocked();
        }

        // Método no permitido
        if (!$this->isMethodAllowed($keyData, $method)) {
            error_log("BLOCKED - Método no permitido - Key: {$keyData['key_name']} - {$method} {$endpoint}");
            return $this->blocked();
        }

        // Válida -> continuar
        $response = $handler->handle($request);
        $this->updateLastUsed($apiKey, $clientIp);

        return $response;
    }

    private function validateApiKey(string $apiKey): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT id, api_key, key_name, is_active, rate_limit, 
                       allowed_endpoints, allowed_methods,
                       allowed_ips, blocked_ips, expires_at
                FROM api_keys WHERE api_key = ?
            ");
            $stmt->execute([$apiKey]);
            $result = $stmt->fetch();
            return $result ?: null;
        } catch (\Exception $e) {
            error_log("Error validating API key: " . $e->getMessage());
            return null;
        }
    }

    private function isIpAllowed(array $keyData, string $clientIp): bool
    {
        if (!empty($keyData['allowed_ips'])) {
            $allowed = array_map('trim', explode(',', $keyData['allowed_ips']));
            return in_array($clientIp, $allowed);
        }
        return true;
    }

    private function isIpBlocked(array $keyData, string $clientIp): bool
    {
        if (empty($keyData['blocked_ips'])) {
            return false;
        }
        $blocked = array_map('trim', explode(',', $keyData['blocked_ips']));
        return in_array($clientIp, $blocked);
    }

    private function isEndpointAllowed(array $keyData, string $endpoint): bool
    {
        if (!empty($keyData['allowed_endpoints'])) {
            $allowed = array_map('trim', explode(',', $keyData['allowed_endpoints']));
            return in_array($endpoint, $allowed) || in_array('*', $allowed);
        }
        return true;
    }

    private function isMethodAllowed(array $keyData, string $method): bool
    {
        $allowed = array_map('trim', explode(',', $keyData['allowed_methods'] ?? 'GET,POST,PUT,DELETE'));
        return in_array($method, $allowed) || in_array('*', $allowed);
    }

    private function updateLastUsed(string $apiKey, string $ip): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE api_keys SET last_used_at = NOW(), last_used_ip = ? WHERE api_key = ?");
            $stmt->execute([$ip, $apiKey]);
        } catch (\Exception $e) {
            error_log("Error updating API key: " . $e->getMessage());
        }
    }

    /**
     * Respuesta genérica - NO revela información alguna
     */
    private function blocked(): Response
    {
        $response = new SlimResponse(503);
        $response->getBody()->write(json_encode([
            'status' => false,
            'error' => self::ERROR_MESSAGE
        ]));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
