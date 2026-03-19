<?php

namespace App\Middleware;

use App\Shared\Database;
use App\Shared\IpIntelligence;
use App\Shared\Sanitizer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

/**
 * Middleware de Validación de API Key
 * 
 * Valida que toda solicitud incluya X-API-Key válida
 * Controla acceso, endpoints permitidos, IPs, etc
 */
class ApiKeyMiddleware implements MiddlewareInterface
{
    // Endpoints que NO requieren API Key (públicos)
    const PUBLIC_ENDPOINTS = [
        '/',
        '/health',
        '/status',
        '/ping',
        '/auth/login',
        '/contacts',
        '/admissions',
    ];

    public function process(Request $request, RequestHandler $handler): Response
    {
        $endpoint = $request->getUri()->getPath();
        $method = $request->getMethod();

        // Excluir endpoints públicos
        if ($this->isPublicEndpoint($endpoint)) {
            return $handler->handle($request);
        }

        // Obtener API Key del header
        $apiKey = $request->getHeaderLine('X-API-Key');

        if (empty($apiKey)) {
            error_log("API request sin X-API-Key - IP: " . Sanitizer::getClientIp() . " - Endpoint: {$endpoint}");
            return $this->createErrorResponse('X-API-Key header requerido', 401);
        }

        // Validar API Key
        $keyData = $this->validateApiKey($apiKey);

        if (!$keyData) {
            error_log("API Key inválida - Key: {$apiKey} - IP: " . Sanitizer::getClientIp() . " - Endpoint: {$endpoint}");
            return $this->createErrorResponse('API Key inválida o expirada', 401);
        }

        // Verificar si la clave está activa
        if (!$keyData['is_active']) {
            error_log("API Key desactivada - Key: {$apiKey} - IP: " . Sanitizer::getClientIp());
            return $this->createErrorResponse('API Key desactivada', 401);
        }

        // Verificar si la clave ha expirado
        if ($keyData['expires_at']) {
            $expiresAt = new \DateTime($keyData['expires_at']);
            $now = new \DateTime();
            if ($now > $expiresAt) {
                error_log("API Key expirada - Key: {$apiKey} - IP: " . Sanitizer::getClientIp());
                return $this->createErrorResponse('API Key expirada', 401);
            }
        }

        // Obtener IP del cliente
        $clientIp = Sanitizer::getClientIp();

        // Verificar restricciones de IP
        if (!$this->isIpAllowed($keyData, $clientIp)) {
            error_log("API Key - IP no permitida - Key: {$apiKey} - IP: {$clientIp}");
            return $this->createErrorResponse('Tu IP no está autorizada para usar esta clave', 403);
        }

        // Verificar si IP está bloqueada
        if ($this->isIpBlocked($keyData, $clientIp)) {
            error_log("API Key - IP bloqueada - Key: {$apiKey} - IP: {$clientIp}");
            return $this->createErrorResponse('Tu IP está bloqueada para esta clave', 403);
        }

        // Verificar endpoints permitidos
        if (!$this->isEndpointAllowed($keyData, $endpoint)) {
            error_log("API Key - Endpoint no permitido - Key: {$apiKey} - Endpoint: {$endpoint}");
            return $this->createErrorResponse('No tienes acceso a este endpoint', 403);
        }

        // Verificar método HTTP permitido
        if (!$this->isMethodAllowed($keyData, $method)) {
            error_log("API Key - Método no permitido - Key: {$apiKey} - Method: {$method}");
            return $this->createErrorResponse('Método HTTP no permitido para esta clave', 403);
        }

        // Log de IP inteligente (para fraude)
        IpIntelligence::logRequest(
            $clientIp,
            $endpoint,
            $method,
            null,
            $_SERVER['HTTP_USER_AGENT'] ?? null,
            $apiKey
        );

        // Procesar solicitud
        $response = $handler->handle($request);

        // Actualizar último uso de la clave
        $this->updateLastUsed($apiKey, $clientIp);

        // Agregar headers de info de API Key
        $response = $response->withHeader('X-API-Key-Valid', 'true');
        $response = $response->withHeader('X-Rate-Limit', (string)$keyData['rate_limit']);

        return $response;
    }

    /**
     * Valida que la API Key exista y sea válida
     */
    private function validateApiKey(string $apiKey): ?array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    id, api_key, key_name, is_active, 
                    rate_limit, allowed_endpoints, allowed_methods,
                    allowed_ips, blocked_ips, expires_at
                FROM api_keys 
                WHERE api_key = ?
            ");
            $stmt->execute([$apiKey]);
            $result = $stmt->fetch();

            return $result ?: null;
        } catch (\Exception $e) {
            error_log("Error validating API key: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Verifica si una IP está permitida para esta clave
     */
    private function isIpAllowed(array $keyData, string $clientIp): bool
    {
        // Si hay IPs permitidas específicas
        if (!empty($keyData['allowed_ips'])) {
            $allowedIps = array_map('trim', explode(',', $keyData['allowed_ips']));
            return in_array($clientIp, $allowedIps);
        }

        // Si no hay restricción, permitir
        return true;
    }

    /**
     * Verifica si una IP está bloqueada
     */
    private function isIpBlocked(array $keyData, string $clientIp): bool
    {
        if (empty($keyData['blocked_ips'])) {
            return false;
        }

        $blockedIps = array_map('trim', explode(',', $keyData['blocked_ips']));
        return in_array($clientIp, $blockedIps);
    }

    /**
     * Verifica si el endpoint está permitido
     */
    private function isEndpointAllowed(array $keyData, string $endpoint): bool
    {
        // Si hay endpoints específicos permitidos
        if (!empty($keyData['allowed_endpoints'])) {
            $allowedEndpoints = array_map('trim', explode(',', $keyData['allowed_endpoints']));
            return in_array($endpoint, $allowedEndpoints) || in_array('*', $allowedEndpoints);
        }

        // Si no hay restricción, permitir
        return true;
    }

    /**
     * Verifica si el método HTTP está permitido
     */
    private function isMethodAllowed(array $keyData, string $method): bool
    {
        $allowedMethods = array_map('trim', explode(',', $keyData['allowed_methods'] ?? 'GET,POST,PUT,DELETE'));
        return in_array($method, $allowedMethods) || in_array('*', $allowedMethods);
    }

    /**
     * Verifica si es endpoint público
     */
    private function isPublicEndpoint(string $endpoint): bool
    {
        return in_array($endpoint, self::PUBLIC_ENDPOINTS);
    }

    /**
     * Actualiza el último uso de la API Key
     */
    private function updateLastUsed(string $apiKey, string $ip): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE api_keys 
                SET last_used_at = NOW(),
                    last_used_ip = ?
                WHERE api_key = ?
            ");
            $stmt->execute([$ip, $apiKey]);
        } catch (\Exception $e) {
            error_log("Error updating API key last used: " . $e->getMessage());
        }
    }

    /**
     * Crea respuesta de error
     */
    private function createErrorResponse(string $message, int $statusCode): Response
    {
        $response = new SlimResponse($statusCode);
        $payload = [
            'exito' => false,
            'error' => $message,
            'code' => $statusCode === 401 ? 'UNAUTHORIZED' : 'FORBIDDEN',
        ];

        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
