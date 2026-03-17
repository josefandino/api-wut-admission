<?php

namespace App\Admission\Controllers;

use App\Shared\Database;
use App\Shared\ApiResponse;
use App\Admission\DTO\CreateAdmissionDTO;
use App\Admission\Validation\DTOValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Ramsey\Uuid\Uuid;

class AdmissionController
{
    public function getClientIp(ServerRequestInterface $request): string
    {
        $headers = $request->getHeaders();
        
        if (!empty($headers['X-Forwarded-For'][0])) {
            return explode(',', $headers['X-Forwarded-For'][0])[0];
        }
        
        if (!empty($headers['X-Real-Ip'][0])) {
            return $headers['X-Real-Ip'][0];
        }
        
        return $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
    }

    public function getHeadersInfo(ServerRequestInterface $request): array
    {
        $headers = $request->getHeaders();
        $info = [];
        
        $headerNames = ['X-Device-Id', 'X-Session-Id', 'X-App-Version', 'User-Agent'];
        
        foreach ($headerNames as $name) {
            $key = strtolower(str_replace(' ', '-', $name));
            $info[$name] = $headers[$key][0] ?? null;
        }
        
        $info['client_ip'] = $this->getClientIp($request);
        
        return $info;
    }

    public function index(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $headersInfo = $this->getHeadersInfo($request);
        
        error_log("Admission access - IP: " . $headersInfo['client_ip'] . 
                  " - Device: " . ($headersInfo['X-Device-Id'] ?? 'N/A') . 
                  " - Session: " . ($headersInfo['X-Session-Id'] ?? 'N/A'));

        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT * FROM admission ORDER BY created_at DESC");
            $admissions = $stmt->fetchAll();
            
            return ApiResponse::success($response, [
                'admissions' => $admissions,
                'access_info' => $headersInfo
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Error al obtener las admisiones: ' . $e->getMessage(), 500);
        }
    }

    public function show(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT * FROM admission WHERE id = ?");
            $stmt->execute([$args['id']]);
            $admission = $stmt->fetch();
            
            if (!$admission) {
                return ApiResponse::error($response, 'Admisión no encontrada', 404);
            }
            
            return ApiResponse::success($response, $admission);
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Error al obtener la admisión: ' . $e->getMessage(), 500);
        }
    }

    public function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $headersInfo = $this->getHeadersInfo($request);
        
        $data = $request->getParsedBody() ?? [];

        if (empty($data)) {
            $contentType = $request->getHeaderLine('Content-Type');
            if (strpos($contentType, 'application/json') !== false) {
                $body = (string) $request->getBody();
                $data = json_decode($body, true) ?? [];
            }
        }

        $errors = DTOValidator::validate($data, CreateAdmissionDTO::rules());
        
        if (!empty($errors)) {
            return ApiResponse::validationError($response, $errors);
        }

        $dto = CreateAdmissionDTO::fromArray($data);

        try {
            $db = Database::getConnection();
            
            $stmt = $db->prepare("SELECT id FROM admission WHERE document = ?");
            $stmt->execute([$dto->document]);
            if ($stmt->fetch()) {
                return ApiResponse::error($response, 'El documento ya está registrado', 409);
            }

            $id = Uuid::uuid4()->toString();
            
            $sql = "INSERT INTO admission (id, name, lastname, type_document, document, phone, email, country, city, address, program) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $db->prepare($sql);
            $stmt->execute([
                $id,
                $dto->name,
                $dto->lastname,
                $dto->typeDocument,
                $dto->document,
                $dto->phone,
                $dto->email,
                $dto->country,
                $dto->city,
                $dto->address,
                $dto->program
            ]);

            error_log("New admission created - ID: {$id}, Document: {$dto->document}, IP: " . $headersInfo['client_ip']);

            return ApiResponse::success($response, [
                'id' => $id,
                'mensaje' => 'Admisión creada exitosamente'
            ], 201);
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Error al crear la admisión: ' . $e->getMessage(), 500);
        }
    }

    public function listAll(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $user = $request->getAttribute('user');
        $xToken = $request->getAttribute('x-token');
        
        error_log("Admin list access - User: " . ($user->sub ?? 'unknown') . " - X-Token: " . substr($xToken ?? '', 0, 8) . "***");

        try {
            $db = Database::getConnection();
            $stmt = $db->query("SELECT id, name, lastname, type_document, document, phone, email, country, city, program, created_at FROM admission ORDER BY created_at DESC");
            $admissions = $stmt->fetchAll();
            
            return ApiResponse::success($response, [
                'total' => count($admissions),
                'admissions' => $admissions
            ]);
        } catch (\Exception $e) {
            return ApiResponse::error($response, 'Error al obtener las admisiones: ' . $e->getMessage(), 500);
        }
    }
}
