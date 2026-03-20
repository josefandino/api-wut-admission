<?php

namespace App\Shared;

use Psr\Http\Message\ResponseInterface;

class ApiResponse
{
    public static function success(ResponseInterface $response, mixed $data, int $statusCode = 200): ResponseInterface
    {
        $response->getBody()->write(json_encode([
            'status' => true,
            'message' => 'Operación exitosa',
            'data' => $data
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    public static function error(ResponseInterface $response, string $message, int $statusCode = 400): ResponseInterface
    {
        $response->getBody()->write(json_encode([
            'status' => false,
            'message' => $message
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    public static function validationError(ResponseInterface $response, array $errors): ResponseInterface
    {
        $response->getBody()->write(json_encode([
            'status' => false,
            'message' => 'Error de validación',
            'errors' => $errors
        ]));
        
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(422);
    }
}



