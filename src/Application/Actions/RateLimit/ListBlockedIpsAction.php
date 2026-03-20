<?php

namespace App\Application\Actions\RateLimit;

use App\Application\Actions\Action;
use App\Shared\RateLimiter;

class ListBlockedIpsAction extends Action
{
    protected function action(): \Psr\Http\Message\ResponseInterface
    {
        error_log("Blocked IPs list access - IP: " . $this->getClientIp());

        try {
            $blockedIps = RateLimiter::getBlockedIps();
            $stats = RateLimiter::getStats();
            
            return $this->respondWithData([
                'blocked_ips' => $blockedIps,
                'statistics' => $stats
            ], 200);
        } catch (\Exception $e) {
            return $this->respondWithError('Error al obtener IPs bloqueadas: ' . $e->getMessage(), 500);
        }
    }
}
