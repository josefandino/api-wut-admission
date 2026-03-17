<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;

class JwtMiddleware
{
    private string $secretKey;

    public function __construct(string $secretKey)
    {
        $this->secretKey = $secretKey;
    }

    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');
        $xToken = $request->getHeaderLine('X-Token');

        if (!$authHeader || !$xToken) {
            $response = new Response(401);
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Missing Authorization header or X-Token'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        if (!preg_match('/Bearer\s+(.+)$/i', $authHeader, $matches)) {
            $response = new Response(401);
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid Authorization header format'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }

        $token = $matches[1];

        try {
            $decoded = JWT::decode($token, new Key($this->secretKey, 'HS256'));
            $request = $request->withAttribute('user', $decoded);
            $request = $request->withAttribute('x-token', $xToken);
            
            return $handler->handle($request);
        } catch (\Exception $e) {
            $response = new Response(401);
            $response->getBody()->write(json_encode([
                'success' => false,
                'error' => 'Invalid or expired token'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
