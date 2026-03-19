<?php

namespace App\Shared;

/**
 * Clase para sanitizar y limpiar inputs de usuarios
 * Protege contra XSS, inyecciones y ataques similares
 */
class Sanitizer
{
    /**
     * Sanitiza un string eliminando caracteres peligrosos
     * @param string $input Entrada a sanitizar
     * @param string $type Tipo de sanitización: 'string', 'email', 'url', 'number'
     * @return string String sanitizado
     */
    public static function sanitize(string $input, string $type = 'string'): string
    {
        // Primero, eliminar espacios en blanco al inicio y final
        $input = trim($input);

        // Aplicar sanitización según el tipo
        return match ($type) {
            'name' => self::sanitizeName($input),
            'lastname' => self::sanitizeLastname($input),
            'email' => self::sanitizeEmail($input),
            'url' => self::sanitizeUrl($input),
            'number' => self::sanitizeNumber($input),
            'phone' => self::sanitizePhone($input),
            'document' => self::sanitizeDocument($input),
            'affair' => self::sanitizeAffair($input),
            'address' => self::sanitizeAddress($input),
            'city' => self::sanitizeCity($input),
            'state' => self::sanitizeState($input),
            'country' => self::sanitizeCountry($input),

            default => self::sanitizeString($input),
        };
    }

    /**
     * Sanitiza un string genérico
     * @param string $input Entrada a sanitizar
     * @return string String limpio
     */
    private static function sanitizeString(string $input): string
    {
        // Remover caracteres de control y nulos
        $input = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input);

        // Convertir caracteres especiales HTML a entidades
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');

        // Remover etiquetas HTML/XML
        $input = strip_tags($input);

        return $input;
    }

    /**
     * Sanitiza un email
     * @param string $input Email a validar y sanitizar
     * @return string Email sanitizado
     */
    private static function sanitizeEmail(string $input): string
    {
        // Remover espacios
        $input = trim($input);

        // Convertir a minúsculas
        $input = strtolower($input);

        // Remover caracteres que no sean válidos en emails
        $input = filter_var($input, FILTER_SANITIZE_EMAIL);

        return $input;
    }

    /**
     * Sanitiza una URL
     * @param string $input URL a sanitizar
     * @return string URL sanitizada
     */
    private static function sanitizeUrl(string $input): string
    {
        // Aplicar filtro de URL
        return filter_var($input, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitiza un número
     * @param string $input Número a sanitizar
     * @return string Número sanitizado
     */
    private static function sanitizeNumber(string $input): string
    {
        // Solo permitir números y puntos/guiones
        return preg_replace('/[^0-9.-]/', '', $input);
    }

    /**
     * Sanitiza un número de teléfono
     * @param string $input Teléfono a sanitizar
     * @return string Teléfono sanitizado
     */
    private static function sanitizePhone(string $input): string
    {
        // Remover caracteres que no sean números, +, -, (), espacios
        return preg_replace('/[^0-9+\-() ]/', '', $input);
    }

    /**
     * Sanitiza un array completo de datos
     * @param array $data Array a sanitizar
     * @param array $rules Reglas de sanitización: ['campo' => 'tipo', ...]
     * @return array Array sanitizado
     */
    public static function sanitizeArray(array $data, array $rules = []): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            // Solo permitir campos alfanuméricos como claves
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $key)) {
                continue;
            }

            // Si no hay regla específica, usar sanitización genérica
            $type = $rules[$key] ?? 'string';

            if (is_string($value)) {
                $sanitized[$key] = self::sanitize($value, $type);
            } elseif (is_array($value)) {
                // Arrays anidados no se permiten por seguridad
                continue;
            } elseif (is_numeric($value)) {
                $sanitized[$key] = $type === 'number' ? $value : self::sanitize((string)$value, $type);
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value;
            }
            // Otros tipos se ignoran
        }

        return $sanitized;
    }

    /**
     * Escapa string para uso seguro en HTML
     * @param string $input String a escapar
     * @return string String escapado
     */
    public static function escape(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Valida si un string contiene patrones maliciosos
     * @param string $input String a validar
     * @return bool True si es seguro, False si detecta patrones maliciosos
     */
    public static function isSafe(string $input): bool
    {
        // Patrones de ataque comunes
        $dangerous = [
            '/<script/i',           // Scripts
            '/javascript:/i',       // JavaScript protocol
            '/on\w+\s*=/i',        // Event handlers (onclick, onerror, etc)
            '/<iframe/i',          // iframes
            '/<embed/i',           // embeds
            '/<object/i',          // objects
            '/union\s+select/i',   // SQL injection
            '/drop\s+table/i',     // SQL injection
            '/exec\s*\(/i',        // Code execution
            '/eval\s*\(/i',        // Code execution
            '/base64_decode/i',    // Base64 decode
        ];

        foreach ($dangerous as $pattern) {
            if (preg_match($pattern, $input)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtiene información del cliente para logging de seguridad
     * @return array Información del cliente
     */
    public static function getClientInfo(): array
    {
        return [
            'ip' => self::getClientIp(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
            'method' => $_SERVER['REQUEST_METHOD'] ?? 'Unknown',
            'timestamp' => date('Y-m-d H:i:s'),
        ];
    }

    /**
     * Obtiene la IP del cliente real
     * @return string IP del cliente
     */
    public static function getClientIp(): string
    {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            return trim($ips[0]);
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        }
    }
}
