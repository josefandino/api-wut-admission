<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;

/**
 * Middleware que agrega headers de seguridad para prevenir ataques comunes
 */
class SecurityHeadersMiddleware implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $response = $handler->handle($request);

        // Content Security Policy - Previene XSS
        $response = $response->withHeader(
            'Content-Security-Policy',
            "default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'"
        );

        // Previene clickjacking
        $response = $response->withHeader('X-Frame-Options', 'DENY');

        // Previene MIME type sniffing
        $response = $response->withHeader('X-Content-Type-Options', 'nosniff');

        // Habilita protección XSS en navegadores antiguos
        $response = $response->withHeader('X-XSS-Protection', '1; mode=block');

        // Referrer Policy - Controla qué información de referrer se envía
        $response = $response->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Feature Policy / Permissions Policy - Controla APIs del navegador
        $response = $response->withHeader(
            'Permissions-Policy',
            'geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()'
        );

        // Habilita HSTS en HTTPS
        if ($this->isHttps($request)) {
            $response = $response->withHeader(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains; preload'
            );
        }

        // Previene acceso a información sensible
        $response = $response->withHeader('X-Permitted-Cross-Domain-Policies', 'none');

        // Asegura que siempre se use HTTPS
        $response = $response->withHeader('Upgrade-Insecure-Requests', '1');

        return $response;
    }

    /**
     * Verifica si la solicitud es HTTPS
     */
    private function isHttps(Request $request): bool
    {
        $scheme = $request->getUri()->getScheme();
        return $scheme === 'https';
    }
}
