<?php

namespace App\Presentation\Routes;

use Slim\App;
use App\Shared\Database;
use App\Domain\User\User;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AuthRoutes
{
    public static function register(App $app, string $jwtSecret): void
    {
        // POST /auth/login - Autenticar usuario y generar JWT token
        $app->post('/auth/login', function (Request $request, Response $response) use ($jwtSecret) {
            $data = json_decode((string)$request->getBody(), true) ?? [];
            
            // Validar datos de entrada
            if (empty($data['username']) || empty($data['password'])) {
                $response->getBody()->write(json_encode([
                    'status' => false,
                    'error' => 'Username and password are required.'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(400);
            }
            
            try {
                // Obtener conexión a BD
                $pdo = Database::getConnection();
                
                // Buscar usuario por username
                $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND deleted_at IS NULL');
                $stmt->execute([$data['username']]);
                $row = $stmt->fetch();
                
                if (!$row) {
                    $response->getBody()->write(json_encode([
                        'status' => false,
                        'error' => 'Invalid credentials.'
                    ]));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(401);
                }
                
                // Crear objeto User
                $user = new User(
                    (int) $row['id'],
                    $row['username'],
                    $row['email'],
                    $row['password_hash'],
                    (bool) $row['is_active'],
                    $row['block_until'],
                    (int) $row['failed_attempts'],
                    $row['last_ip_address'],
                    $row['last_user_agent'],
                    (int) $row['status_id'],
                    $row['role'],
                    $row['last_login_at'],
                    $row['password_changed_at'],
                    $row['login_attempts_window'],
                    $row['account_locked_until'],
                    $row['created_at'],
                    $row['updated_at'],
                    $row['deleted_at']
                );
                
                // Verificar si usuario está activo
                if (!$user->isActive()) {
                    $response->getBody()->write(json_encode([
                        'status' => false,
                        'error' => 'Invalid credentials.'
                    ]));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(401);
                }
                
                // Verificar si usuario está bloqueado
                if ($user->getBlockUntil()) {
                    $blockUntil = new \DateTime($user->getBlockUntil());
                    $now = new \DateTime();
                    
                    if ($now < $blockUntil) {
                        $response->getBody()->write(json_encode([
                            'status' => false,
                            'error' => 'Account is temporarily blocked due to multiple failed login attempts.',
                            'unblock_at' => $blockUntil->format('Y-m-d H:i:s')
                        ]));
                        return $response
                            ->withHeader('Content-Type', 'application/json')
                            ->withStatus(429);
                    }
                }
                
                // Verificar contraseña
                if (!password_verify($data['password'], $user->getPasswordHash())) {
                    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                    
                    // Registrar intento fallido
                    $updateStmt = $pdo->prepare('UPDATE users SET failed_attempts = failed_attempts + 1, last_ip_address = ?, last_user_agent = ? WHERE id = ?');
                    $updateStmt->execute([$ipAddress, $userAgent, $user->getId()]);
                    
                    // Bloquear después de 5 intentos fallidos
                    if ($user->getFailedAttempts() >= 4) {
                        $blockUntil = (new \DateTime())->modify('+30 minutes')->format('Y-m-d H:i:s');
                        $blockStmt = $pdo->prepare('UPDATE users SET block_until = ? WHERE id = ?');
                        $blockStmt->execute([$blockUntil, $user->getId()]);
                    }
                    
                    $response->getBody()->write(json_encode([
                        'status' => false,
                        'error' => 'Invalid credentials.'
                    ]));
                    return $response
                        ->withHeader('Content-Type', 'application/json')
                        ->withStatus(401);
                }

                // Registrar login exitoso
                $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
                $loginStmt = $pdo->prepare('UPDATE users SET failed_attempts = 0, last_login_at = NOW(), last_ip_address = ?, last_user_agent = ? WHERE id = ?');
                $loginStmt->execute([$ipAddress, $userAgent, $user->getId()]);
                
                // Generar JWT token
                $now = time();
                $payload = [
                    'iat' => $now,
                    'exp' => $now + (24 * 60 * 60), // Válido por 24 horas
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole()
                ];
                
                $token = JWT::encode($payload, $jwtSecret, 'HS256');
                
                // Respuesta exitosa
                $response->getBody()->write(json_encode([
                    'status' => true,
                    'data' => [
                        'token' => $token,
                        'user' => [
                            'id' => $user->getId(),
                            'username' => $user->getUsername(),
                            'email' => $user->getEmail(),
                            'role' => $user->getRole()
                        ]
                    ]
                ]));
                
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(200);
                    
            } catch (\Exception $e) {
                error_log("Login error: " . $e->getMessage());
                
                $response->getBody()->write(json_encode([
                    'status' => false,
                    'error' => 'Authentication error'
                ]));
                return $response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(500);
            }
        });
    }
}
