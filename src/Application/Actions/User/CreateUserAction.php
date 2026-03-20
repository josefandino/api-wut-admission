<?php

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\User\SqlUserRepository;
use App\Shared\Sanitizer;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class CreateUserAction extends Action
{
    private SqlUserRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlUserRepository();
    }

    public static function rules(): array
    {
        return [
            'username' => v::stringType()->notBlank()->length(3, 100),
            'email' => v::stringType()->notBlank()->email()->length(5, 150),
            'password' => v::stringType()->notBlank()->length(8, 255),
            'role' => v::stringType()->in(['admin', 'moderator', 'user'])->optional(),
        ];
    }

    public static function allowedFields(): array
    {
        return ['username', 'email', 'password', 'role'];
    }

    protected function action(): Response
    {
        $formData = $this->getFormData();
        
        if (empty($formData)) {
            $formData = $this->getJsonData();
        }

        if (empty($formData)) {
            return $this->respondWithError('No data received', 400);
        }

        // Sanitizar datos
        $sanitizationRules = [
            'username' => 'string',
            'email' => 'email',
            'password' => 'string',
            'role' => 'string',
        ];
        $formData = Sanitizer::sanitizeArray($formData, $sanitizationRules);

        // Validar seguridad de datos
        foreach (['username', 'email'] as $field) {
            if (isset($formData[$field]) && !Sanitizer::isSafe($formData[$field])) {
                error_log("Potential security threat detected in field '{$field}' - IP: " . $this->getClientIp());
                return $this->respondWithError('Invalid data detected', 400);
            }
        }

        $errors = $this->validate($formData);
        
        if (!empty($errors)) {
            return $this->respondWithError('Validation error', 422, $errors);
        }

        try {
            // Verificar que el username no exista
            $existingUser = $this->repository->findByUsername($formData['username']);
            if ($existingUser) {
                error_log("Attempt to create user with existing username: {$formData['username']}, IP: " . $this->getClientIp());
                return $this->respondWithError('Username already exists', 409);
            }

            // Verificar que el email no exista
            $existingEmail = $this->repository->findByEmail($formData['email']);
            if ($existingEmail) {
                error_log("Attempt to create user with existing email: {$formData['email']}, IP: " . $this->getClientIp());
                return $this->respondWithError('Email already exists', 409);
            }

            // Hash de contraseña
            $passwordHash = password_hash($formData['password'], PASSWORD_BCRYPT, ['cost' => 10]);

            // Crear usuario
            $role = $formData['role'] ?? 'user';
            $ipAddress = $this->getClientIp();
            $userAgent = $this->request->getServerParams()['HTTP_USER_AGENT'] ?? '';

            $user = $this->repository->create(
                $formData['username'],
                $formData['email'],
                $passwordHash,
                $role,
                $ipAddress,
                $userAgent
            );

            error_log("New user created - ID: {$user->getId()}, Username: {$user->getUsername()}, Role: {$user->getRole()}, IP: {$ipAddress}");

            return $this->respondWithCreated('User created successfully');
        } catch (\Exception $e) {
            error_log("Error creating user: " . $e->getMessage());
            return $this->respondWithError('Error creating user', 500);
        }
    }

    private function validate(array $data): array
    {
        $errors = [];

        try {
            v::keySet(
                v::key('username', v::stringType()->notBlank()->length(3, 100), false),
                v::key('email', v::stringType()->notBlank()->email()->length(5, 150), false),
                v::key('password', v::stringType()->notBlank()->length(8, 255), false),
                v::key('role', v::stringType()->in(['admin', 'moderator', 'user']), true)
            )->assert($data);
        } catch (NestedValidationException $e) {
            $errors = $e->getMessages();
        }

        return $errors;
    }
}
