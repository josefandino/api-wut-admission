<?php

namespace App\Application\Actions\ApiKey;

use App\Application\Actions\Action;
use App\Shared\Database;

class ListApiKeysAction extends Action
{
    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        error_log("API Keys list access - IP: " . $this->getClientIp());

        try {
            $db = Database::getConnection();
            
            // Listar todas las API Keys (ocultando datos sensibles)
            $stmt = $db->query("
                SELECT 
                    id, key_name, client_name, client_email,
                    is_active, is_admin,
                    rate_limit, rate_limit_window,
                    allowed_endpoints, allowed_methods,
                    last_used_at, last_used_ip,
                    created_at, expires_at,
                    CONCAT(SUBSTRING(api_key, 1, 10), '...') as api_key_masked
                FROM api_keys 
                ORDER BY created_at DESC
            ");
            
            $keys = $stmt->fetchAll();

            return $this->respondWithData([
                'api_keys' => $keys
            ]);
        } catch (\Exception $e) {
            return $this->respondWithError('Error al obtener API Keys: ' . $e->getMessage(), 500);
        }
    }
}
