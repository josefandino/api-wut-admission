<?php

namespace App\Shared;

/**
 * Clase para obtener inteligencia de IP - País, Proveedor, Fraude
 * Almacena logs para detección de fraude
 */
class IpIntelligence
{
    /**
     * Obtiene información de una IP (Geolocalización, ISP, etc)
     * Nota: Para producción, usar APIs como MaxMind, IPStack, etc
     * Por ahora, simulamos la respuesta
     */
    public static function getIpInfo(string $ip): array
    {
        // En producción, integrar con servicios como:
        // - MaxMind GeoIP2 (mejor opción)
        // - IPStack (gratuito)
        // - ip-api.com
        // - geoip.google.com (deprecated pero todavía funciona)
        
        // Para desarrollo, retornamos estructura base
        $info = [
            'ip' => $ip,
            'country_code' => self::geolocateIp($ip)['country_code'] ?? 'UNKNOWN',
            'country_name' => self::geolocateIp($ip)['country_name'] ?? 'Unknown Country',
            'isp_provider' => self::getIspProvider($ip) ?? 'Unknown ISP',
            'organization' => self::getOrganization($ip) ?? 'Unknown Organization',
            'timezone' => self::getTimezone($ip) ?? 'UTC',
            'latitude' => self::geolocateIp($ip)['latitude'] ?? null,
            'longitude' => self::geolocateIp($ip)['longitude'] ?? null,
            'is_vpn' => self::detectVpn($ip),
            'is_proxy' => self::detectProxy($ip),
            'is_bot' => self::detectBot($_SERVER['HTTP_USER_AGENT'] ?? ''),
        ];

        return $info;
    }

    /**
     * Almacena log de petición para análisis de fraude
     */
    public static function logRequest(
        string $ip,
        string $endpoint,
        string $method,
        ?array $ipInfo = null,
        ?string $userAgent = null,
        ?string $apiKey = null,
        ?int $responseCode = null,
        ?int $responseTime = null,
        ?string $requestBody = null
    ): void {
        try {
            $db = Database::getConnection();

            // Obtener info de IP si no se proporciona
            if ($ipInfo === null) {
                $ipInfo = self::getIpInfo($ip);
            }

            // Calcular fraude score
            $fraudScore = self::calculateFraudScore($ipInfo, $endpoint, $method);

            // Detectar si es sospechoso
            $isSuspicious = $fraudScore >= 50;

            // Detectar tipo de amenaza
            $threatType = self::detectThreatType($ipInfo, $fraudScore);

            // Preparar datos
            $stmt = $db->prepare("
                INSERT INTO ip_logs (
                    ip_address, endpoint, method, 
                    country_code, country_name, 
                    isp_provider, organization, timezone, 
                    latitude, longitude,
                    is_vpn, is_proxy, is_bot,
                    user_agent, api_key_used,
                    response_code, response_time,
                    request_body,
                    fraud_score, is_suspicious, threat_type
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $ip,
                $endpoint,
                $method,
                $ipInfo['country_code'] ?? 'UNKNOWN',
                $ipInfo['country_name'] ?? 'Unknown',
                $ipInfo['isp_provider'] ?? 'Unknown',
                $ipInfo['organization'] ?? 'Unknown',
                $ipInfo['timezone'] ?? 'UTC',
                $ipInfo['latitude'] ?? null,
                $ipInfo['longitude'] ?? null,
                $ipInfo['is_vpn'] ? 1 : 0,
                $ipInfo['is_proxy'] ? 1 : 0,
                $ipInfo['is_bot'] ? 1 : 0,
                $userAgent,
                $apiKey,
                $responseCode,
                $responseTime,
                $requestBody ? substr($requestBody, 0, 1000) : null, // Limitar a 1000 chars
                $fraudScore,
                $isSuspicious ? 1 : 0,
                $threatType
            ]);

            // Log si es sospechoso
            if ($isSuspicious) {
                error_log("FRAUD ALERT: IP {$ip} - Score: {$fraudScore} - Type: {$threatType} - Endpoint: {$endpoint}");
            }
        } catch (\Exception $e) {
            error_log("Error logging IP request: " . $e->getMessage());
        }
    }

    /**
     * Calcula puntuación de fraude (0-100)
     */
    private static function calculateFraudScore(array $ipInfo, string $endpoint, string $method): int
    {
        $score = 0;

        // VPN detectado: +30 puntos
        if ($ipInfo['is_vpn'] ?? false) {
            $score += 30;
        }

        // Proxy detectado: +25 puntos
        if ($ipInfo['is_proxy'] ?? false) {
            $score += 25;
        }

        // Bot detectado: +20 puntos
        if ($ipInfo['is_bot'] ?? false) {
            $score += 20;
        }

        // Países de alto riesgo: +15 puntos
        $highRiskCountries = ['KP', 'IR', 'SY', 'CU', 'XX']; // Corea del Norte, Irán, etc
        if (in_array($ipInfo['country_code'] ?? '', $highRiskCountries)) {
            $score += 15;
        }

        // Endpoint sensible con POST: +10 puntos
        if (in_array($endpoint, ['/contacts', '/admissions', '/registration']) && $method === 'POST') {
            $score += 10;
        }

        // Limitar a 100
        return min($score, 100);
    }

    /**
     * Detecta tipo de amenaza basado en fraude score
     */
    private static function detectThreatType(array $ipInfo, int $fraudScore): ?string
    {
        if ($fraudScore >= 80) {
            return 'CRITICAL_THREAT';
        } elseif ($fraudScore >= 60) {
            return 'HIGH_RISK';
        } elseif ($fraudScore >= 40) {
            return 'MEDIUM_RISK';
        } elseif ($fraudScore >= 20) {
            return 'LOW_RISK';
        }

        return null;
    }

    /**
     * Geolocaliza una IP (simulado - en prod usar MaxMind)
     */
    private static function geolocateIp(string $ip): array
    {
        // En producción, usar: https://github.com/maxmind/GeoIP2-php
        // Para desarrollo, retornamos estructura por defecto
        
        // IPs locales
        if (in_array($ip, ['127.0.0.1', '::1', '192.168.1.1', 'localhost'])) {
            return [
                'country_code' => 'LOCAL',
                'country_name' => 'Local Network',
                'latitude' => 0,
                'longitude' => 0,
            ];
        }

        // Simulación simple (en prod usar API real)
        return [
            'country_code' => 'CO', // Por defecto Colombia
            'country_name' => 'Colombia',
            'latitude' => 4.5709,
            'longitude' => -74.2973,
        ];
    }

    /**
     * Obtiene ISP/Proveedor (simulado)
     */
    private static function getIspProvider(string $ip): ?string
    {
        // En producción, usar WHOIS o API de geolocalización completa
        $patterns = [
            '192.168' => 'Local Network',
            '10.' => 'Local Network',
            '172.' => 'Local Network',
        ];

        foreach ($patterns as $pattern => $isp) {
            if (strpos($ip, $pattern) === 0) {
                return $isp;
            }
        }

        // Para IPs públicas, retornar valor genérico
        return 'ISP Provider (Unknown)';
    }

    /**
     * Obtiene organización
     */
    private static function getOrganization(string $ip): ?string
    {
        // En producción, usar WHOIS
        if (in_array($ip, ['127.0.0.1', '::1'])) {
            return 'Localhost';
        }
        return null;
    }

    /**
     * Obtiene timezone de IP
     */
    private static function getTimezone(string $ip): ?string
    {
        // En producción, usar datos de geolocalización
        return 'America/Bogota'; // Por defecto Colombia
    }

    /**
     * Detecta VPN
     */
    private static function detectVpn(string $ip): bool
    {
        // En producción, usar servicio como:
        // - MaxMind VPN Detection
        // - IPQualityScore
        // - IP2Proxy
        
        // Por ahora, retornar false
        return false;
    }

    /**
     * Detecta Proxy
     */
    private static function detectProxy(string $ip): bool
    {
        // En producción, usar servicio especializado
        return false;
    }

    /**
     * Detecta si es bot
     */
    private static function detectBot(string $userAgent): bool
    {
        $botPatterns = [
            'bot',
            'crawler',
            'spider',
            'scraper',
            'curl',
            'wget',
            'python',
            'java',
            'ruby',
            'perl',
            'golang',
        ];

        $userAgentLower = strtolower($userAgent);

        foreach ($botPatterns as $pattern) {
            if (strpos($userAgentLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Obtiene logs sospechosos
     */
    public static function getSuspiciousLogs(int $limit = 100): array
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("
                SELECT * FROM ip_logs 
                WHERE is_suspicious = TRUE
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $stmt->execute([$limit]);
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("Error getting suspicious logs: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene estadísticas de fraude
     */
    public static function getFraudStatistics(): array
    {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->query("
                SELECT 
                    COUNT(*) as total_logs,
                    SUM(CASE WHEN is_suspicious=1 THEN 1 ELSE 0 END) as suspicious_count,
                    SUM(CASE WHEN is_vpn=1 THEN 1 ELSE 0 END) as vpn_count,
                    SUM(CASE WHEN is_proxy=1 THEN 1 ELSE 0 END) as proxy_count,
                    SUM(CASE WHEN is_bot=1 THEN 1 ELSE 0 END) as bot_count,
                    AVG(fraud_score) as avg_fraud_score,
                    MAX(fraud_score) as max_fraud_score
                FROM ip_logs
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ");
            
            return $stmt->fetch() ?: [];
        } catch (\Exception $e) {
            error_log("Error getting fraud statistics: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene países por incidentes
     */
    public static function getIncidentsByCountry(): array
    {
        try {
            $db = Database::getConnection();
            
            $stmt = $db->query("
                SELECT 
                    country_code,
                    country_name,
                    COUNT(*) as incident_count,
                    SUM(CASE WHEN is_suspicious=1 THEN 1 ELSE 0 END) as suspicious_count,
                    AVG(fraud_score) as avg_fraud_score
                FROM ip_logs
                WHERE created_at > DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY country_code
                ORDER BY incident_count DESC
            ");
            
            return $stmt->fetchAll();
        } catch (\Exception $e) {
            error_log("Error getting incidents by country: " . $e->getMessage());
            return [];
        }
    }
}
