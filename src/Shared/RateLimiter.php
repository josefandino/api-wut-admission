<?php

namespace App\Shared;

/**
 * Clase RateLimiter Pro para control de ataques DDoS y fuerza bruta
 * 
 * Estrategia:
 * - 1 petición: Permitida (contador = 1)
 * - 2 peticiones en 1 minuto: Permitidas (contador = 2)
 * - 3+ peticiones en 1 minuto: BLOQUEADA por 24 horas
 * 
 * Cada IP tiene un contador independiente por endpoint
 */
class RateLimiter
{
    // Configuración de límites
    const MAX_REQUESTS_PER_MINUTE = 3;
    const BLOCK_DURATION_HOURS = 24;
    const CLEANUP_INTERVAL_HOURS = 48;
    const SLOW_DOWN_REQUESTS = 2;
    const WARNING_THRESHOLD = 2;

    // Endpoints excluidos del rate limiting
    const EXCLUDED_ENDPOINTS = [
        '/',
        '/health',
        '/status',
        '/ping',
        '/auth/login',
        '/auth/register',
        '/contacts',
        '/admissions',
    ];

    // Endpoints con límites especiales (más restrictivos)
    const STRICT_ENDPOINTS = [
        '/contacts',
        '/admissions',
        '/login',
        '/register',
    ];

    /**
     * Verifica si una IP debe ser bloqueada
     * 
     * @param string $ip IP del cliente
     * @param string $endpoint Endpoint solicitado
     * @param string $method Método HTTP
     * @return array ['allowed' => bool, 'message' => string, 'retry_after' => int]
     */
    public static function checkRateLimit(string $ip, string $endpoint, string $method): array
    {
        // Validar IP
        if (!self::isValidIp($ip)) {
            return ['allowed' => false, 'message' => 'IP inválida', 'retry_after' => 0];
        }

        // Excluir endpoints públicos
        if (self::isExcludedEndpoint($endpoint)) {
            return ['allowed' => true, 'message' => 'Endpoint excluido', 'retry_after' => 0];
        }

        // Solo aplicar rate limiting a POST, PUT, DELETE, PATCH
        if (!in_array($method, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            return ['allowed' => true, 'message' => 'Método permitido', 'retry_after' => 0];
        }

        $key = self::generateKey($ip, $endpoint);
        
        // Obtener estadísticas actuales
        $stats = self::getIpStats($ip, $endpoint);

        // Si está bloqueado, verificar si el bloqueo ha expirado
        if ($stats['is_blocked'] && $stats['blocked_until']) {
            $now = new \DateTime();
            $blockedUntil = new \DateTime($stats['blocked_until']);
            
            if ($now < $blockedUntil) {
                $retryAfter = $blockedUntil->getTimestamp() - $now->getTimestamp();
                error_log("Rate limit BLOCKED: IP {$ip} - Endpoint: {$endpoint} - Retry after: {$retryAfter}s");
                return [
                    'allowed' => false,
                    'message' => "Tu IP ha sido bloqueada por demasiadas peticiones. Intenta de nuevo en 24 horas.",
                    'retry_after' => $retryAfter,
                ];
            } else {
                // El bloqueo ha expirado, resetear
                self::resetStats($ip, $endpoint);
                $stats = self::getIpStats($ip, $endpoint);
            }
        }

        // Obtener límite según endpoint
        $maxRequests = self::isStrictEndpoint($endpoint) ? 2 : self::MAX_REQUESTS_PER_MINUTE;
        $currentRequests = $stats['request_count'] + 1;

        // Si ya alcanzó el límite, BLOQUEAR
        if ($currentRequests > $maxRequests) {
            $blockedUntil = new \DateTime('now', new \DateTimeZone('UTC'));
            $blockedUntil->add(new \DateInterval('PT' . self::BLOCK_DURATION_HOURS . 'H'));
            
            self::updateStats(
                $ip,
                $endpoint,
                $currentRequests,
                $blockedUntil->format('Y-m-d H:i:s'),
                true,
                'Límite de peticiones excedido'
            );

            error_log("Rate limit BLOCKED: IP {$ip} - Endpoint: {$endpoint} - Requests: {$currentRequests}/{$maxRequests}");
            
            return [
                'allowed' => false,
                'message' => "Has excedido el límite de peticiones. Tu IP está bloqueada por 24 horas.",
                'retry_after' => self::BLOCK_DURATION_HOURS * 3600,
            ];
        }

        // Incrementar contador
        self::updateStats(
            $ip,
            $endpoint,
            $currentRequests,
            null,
            false,
            null
        );

        // Generar warning si está cerca del límite
        $remainingRequests = $maxRequests - $currentRequests;
        $message = "Solicitud permitida. Peticiones restantes: {$remainingRequests}/{$maxRequests}";
        
        if ($remainingRequests <= 1) {
            error_log("Rate limit WARNING: IP {$ip} - Endpoint: {$endpoint} - Requests: {$currentRequests}/{$maxRequests}");
            $message = "⚠️ Última petición permitida antes del bloqueo de 24 horas";
        }

        return [
            'allowed' => true,
            'message' => $message,
            'retry_after' => 0,
        ];
    }

    /**
     * Obtiene estadísticas de una IP para un endpoint
     */
    private static function getIpStats(string $ip, string $endpoint): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT 
                    request_count,
                    blocked_until,
                    is_blocked,
                    first_request_at,
                    last_request_at
                FROM rate_limiting 
                WHERE ip_address = ? AND endpoint = ?
            ");
            $stmt->execute([$ip, $endpoint]);
            $result = $stmt->fetch();

            if ($result) {
                return $result;
            }

            // Si no existe, crear registro
            return [
                'request_count' => 0,
                'blocked_until' => null,
                'is_blocked' => false,
                'first_request_at' => date('Y-m-d H:i:s'),
                'last_request_at' => date('Y-m-d H:i:s'),
            ];
        } catch (\Exception $e) {
            error_log("Error getting rate limit stats: " . $e->getMessage());
            return [
                'request_count' => 0,
                'blocked_until' => null,
                'is_blocked' => false,
            ];
        }
    }

    /**
     * Actualiza estadísticas de una IP
     */
    private static function updateStats(
        string $ip,
        string $endpoint,
        int $requestCount,
        ?string $blockedUntil,
        bool $isBlocked,
        ?string $blockReason
    ): void {
        try {
            $db = Database::getConnection();
            
            // Verificar si existe
            $checkStmt = $db->prepare("SELECT id FROM rate_limiting WHERE ip_address = ? AND endpoint = ?");
            $checkStmt->execute([$ip, $endpoint]);
            
            if ($checkStmt->fetch()) {
                // Actualizar
                $stmt = $db->prepare("
                    UPDATE rate_limiting 
                    SET request_count = ?,
                        blocked_until = ?,
                        is_blocked = ?,
                        block_reason = ?,
                        last_request_at = NOW()
                    WHERE ip_address = ? AND endpoint = ?
                ");
                $stmt->execute([
                    $requestCount,
                    $blockedUntil,
                    $isBlocked ? 1 : 0,
                    $blockReason,
                    $ip,
                    $endpoint
                ]);
            } else {
                // Insertar
                $stmt = $db->prepare("
                    INSERT INTO rate_limiting 
                    (ip_address, endpoint, request_count, blocked_until, is_blocked, block_reason, user_agent, first_request_at, last_request_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([
                    $ip,
                    $endpoint,
                    $requestCount,
                    $blockedUntil,
                    $isBlocked ? 1 : 0,
                    $blockReason,
                    $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
                ]);
            }
        } catch (\Exception $e) {
            error_log("Error updating rate limit stats: " . $e->getMessage());
        }
    }

    /**
     * Resetea estadísticas de una IP
     */
    private static function resetStats(string $ip, string $endpoint): void
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                UPDATE rate_limiting 
                SET request_count = 0,
                    is_blocked = FALSE,
                    blocked_until = NULL,
                    block_reason = NULL
                WHERE ip_address = ? AND endpoint = ?
            ");
            $stmt->execute([$ip, $endpoint]);
        } catch (\Exception $e) {
            error_log("Error resetting rate limit stats: " . $e->getMessage());
        }
    }

    /**
     * Limpia registros antiguos (mantenimiento)
     */
    public static function cleanup(): void
    {
        try {
            $db = Database::getConnection();
            
            // Eliminar registros más antiguos que CLEANUP_INTERVAL_HOURS y no bloqueados
            $stmt = $db->prepare("
                DELETE FROM rate_limiting 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? HOUR)
                AND is_blocked = FALSE
                AND request_count < 3
            ");
            $stmt->execute([self::CLEANUP_INTERVAL_HOURS]);
            
            $rowsDeleted = $stmt->rowCount();
            if ($rowsDeleted > 0) {
                error_log("Rate limiting cleanup: {$rowsDeleted} records deleted");
            }
        } catch (\Exception $e) {
            error_log("Error during rate limiting cleanup: " . $e->getMessage());
        }
    }

    /**
     * Desbloquea una IP manualmente (para admins)
     */
    public static function unblock(string $ip, ?string $endpoint = null): bool
    {
        try {
            $db = Database::getConnection();
            
            if ($endpoint) {
                $stmt = $db->prepare("
                    UPDATE rate_limiting 
                    SET is_blocked = FALSE, blocked_until = NULL, request_count = 0
                    WHERE ip_address = ? AND endpoint = ?
                ");
                $stmt->execute([$ip, $endpoint]);
            } else {
                $stmt = $db->prepare("
                    UPDATE rate_limiting 
                    SET is_blocked = FALSE, blocked_until = NULL, request_count = 0
                    WHERE ip_address = ?
                ");
                $stmt->execute([$ip]);
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error unblocking IP: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene lista de IPs bloqueadas
     */
    public static function getBlockedIps(): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->query("
                SELECT 
                    ip_address,
                    endpoint,
                    request_count,
                    blocked_until,
                    block_reason,
                    user_agent,
                    last_request_at
                FROM rate_limiting 
                WHERE is_blocked = TRUE
                ORDER BY blocked_until DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("Error getting blocked IPs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadísticas globales
     */
    public static function getStats(): array
    {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total_tracked_ips,
                    SUM(CASE WHEN is_blocked = TRUE THEN 1 ELSE 0 END) as blocked_ips,
                    AVG(request_count) as avg_requests,
                    MAX(request_count) as max_requests
                FROM rate_limiting
            ");
            
            return $stmt->fetch();
        } catch (\Exception $e) {
            error_log("Error getting rate limit statistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Valida que una IP sea válida
     */
    private static function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }

    /**
     * Verifica si un endpoint está excluido
     */
    private static function isExcludedEndpoint(string $endpoint): bool
    {
        return in_array($endpoint, self::EXCLUDED_ENDPOINTS);
    }

    /**
     * Verifica si un endpoint tiene límites estrictos
     */
    private static function isStrictEndpoint(string $endpoint): bool
    {
        return in_array($endpoint, self::STRICT_ENDPOINTS);
    }

    /**
     * Genera una clave única para IP + endpoint
     */
    private static function generateKey(string $ip, string $endpoint): string
    {
        return "{$ip}:{$endpoint}";
    }
}
