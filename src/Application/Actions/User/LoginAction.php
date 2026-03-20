<?php

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Domain\User\UserRepository;
use App\Shared\Sanitizer;
use Firebase\JWT\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as v;

class LoginAction extends Action
{
    private UserRepository $userRepository;
    private string $jwtSecret;

    public function __construct(UserRepository $userRepository, string $jwtSecret)
    {
        $this->userRepository = $userRepository;
        $this->jwtSecret = $jwtSecret;
    }

    public static function rules(): array
    {
        return [
            'username' => v::stringType()->notBlank()->length(3, 100),
            'password' => v::stringType()->notBlank()->length(6, 255),
        ];
    }

    protected function action(): Response
    {
        $data = $this->getJsonData();

        if (empty($data)) {
            return $this->respondWithError('No data received', 400);
        }

        // Validar datos requeridos
        if (empty($data['username']) || empty($data['password'])) {
            return $this->respondWithError('Username and password are required.', 400);
        }

        $username = Sanitizer::sanitize($data['username'], 'string');
        $password = $data['password']; // No sanitizar password

        // Obtener usuario por username
        $user = $this->userRepository->findByUsername($username);
        
        // DEBUG
        $debugMsg = "Username: $username, User found: " . ($user ? "YES" : "NO");
        if ($user) {
            $debugMsg .= ", ID: " . $user->getId() . ", Block until: " . ($user->getBlockUntil() ?? "NULL");
        }
        file_put_contents('C:\\xampp\\htdocs\\api\\api-wut-admission\\login_debug.txt', $debugMsg . "\n", FILE_APPEND);

        // Usuario no encontrado o inactivo - respuesta genérica por seguridad
        if (!$user || !$user->isActive()) {
            return $this->respondWithError('Invalid credentials.', 401);
        }

        // Verificar si el usuario está bloqueado
        if ($user->getBlockUntil()) {
            $blockUntil = new \DateTime($user->getBlockUntil());
            $now = new \DateTime();

            if ($now < $blockUntil) {
                error_log("Failed login attempt - Account blocked: {$username}, IP: " . $this->getClientIp());
                
                $this->response->getBody()->write(json_encode([
                    'status' => false,
                    'error' => 'LOGIN_ACTION_BLOCK_TEST_UNIQUE_MESSAGE_12345',
                    'unblock_at' => $blockUntil->format('Y-m-d H:i:s')
                ]));

                return $this->response
                    ->withHeader('Content-Type', 'application/json')
                    ->withStatus(429);
            }
        }

        // Verificar contraseña
        if (!password_verify($password, $user->getPasswordHash())) {
            $ipAddress = $this->getClientIp();
            $userAgent = $this->request->getServerParams()['HTTP_USER_AGENT'] ?? '';

            // Registrar intento fallido
            $this->userRepository->recordFailedAttempt($user->getId(), $ipAddress, $userAgent);

            // Bloquear después de 5 intentos fallidos
            if ($user->getFailedAttempts() >= 4) { // 5 intentos totales (4 + 1 actual)
                $this->userRepository->blockUser($user->getId(), 30);
                error_log("User account blocked due to multiple failed attempts: {$username}, IP: {$ipAddress}");
            }

            error_log("Failed login attempt - Invalid password: {$username}, IP: {$ipAddress}");
            
            return $this->respondWithError('Invalid credentials.', 401);
        }

        // Login exitoso
        $ipAddress = $this->getClientIp();
        $userAgent = $this->request->getServerParams()['HTTP_USER_AGENT'] ?? '';

        $this->userRepository->recordSuccessfulLogin($user->getId(), $ipAddress, $userAgent);

        // Generar JWT
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + (24 * 60 * 60), // 24 horas
            'id' => $user->getId(),
            'username' => $user->getUsername(),
            'email' => $user->getEmail(),
            'role' => $user->getRole()
        ];

        $token = JWT::encode($payload, $this->jwtSecret, 'HS256');

        error_log("Successful login: {$username}, IP: {$ipAddress}");

        return $this->respondWithData([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()
            ]
        ], 200, 'Login exitoso');
    }
}
