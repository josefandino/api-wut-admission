<?php

namespace App\Application\Actions;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

abstract class Action
{
    protected Request $request;
    protected Response $response;
    protected array $args;

    public function __invoke(Request $request, Response $response, array $args): Response
    {
        $this->request = $request;
        $this->response = $response;
        $this->args = $args;

        return $this->action();
    }

    abstract protected function action(): Response;

    protected function getFormData(): array
    {
        return $this->request->getParsedBody() ?? [];
    }

    protected function getJsonData(): array
    {
        $contentType = $this->request->getHeaderLine('Content-Type');
        if (strpos($contentType, 'application/json') !== false) {
            $body = (string) $this->request->getBody();
            return json_decode($body, true) ?? [];
        }
        return [];
    }

    protected function getHeader(string $name): ?string
    {
        return $this->request->getHeaderLine($name) ?: null;
    }

    protected function getClientIp(): string
    {
        $headers = $this->request->getHeaders();
        
        if (!empty($headers['X-Forwarded-For'][0])) {
            return explode(',', $headers['X-Forwarded-For'][0])[0];
        }
        
        if (!empty($headers['X-Real-Ip'][0])) {
            return $headers['X-Real-Ip'][0];
        }
        
        return $this->request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
    }

    protected function getHeadersInfo(): array
    {
        $headers = $this->request->getHeaders();
        $info = [];
        
        $headerNames = ['X-Device-Id', 'X-Session-Id', 'X-App-Version', 'User-Agent'];
        
        foreach ($headerNames as $name) {
            $key = strtolower(str_replace(' ', '-', $name));
            $info[$name] = $headers[$key][0] ?? null;
        }
        
        $info['client_ip'] = $this->getClientIp();
        
        return $info;
    }

    protected function respondWithData(mixed $data = null, int $statusCode = 200, string $message = ''): Response
    {
        $dataArray = is_array($data) ? $data : ($data !== null ? [$data] : []);

        $payload = [
            'status' => true,
            'message' => $message,
            'data' => $dataArray,
            'total' => is_array($data) ? count($data) : ($data !== null ? 1 : 0),
        ];

        $this->response->getBody()->write(json_encode($payload));

        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }

    protected function respondWithCreated(string $message = ''): Response
    {
        $payload = [
            'status' => true,
            'message' => $message,
            'data' => [],
            'total' => 1,
        ];

        $this->response->getBody()->write(json_encode($payload));

        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);
    }

    protected function respondWithError(string $message, int $statusCode = 400, array $errors = null): Response
    {
        $payload = [
            'status' => false,
            'error' => $message
        ];

        if ($errors !== null) {
            $payload['errors'] = $errors;
        }

        $this->response->getBody()->write(json_encode($payload));
        
        return $this->response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode);
    }
}
