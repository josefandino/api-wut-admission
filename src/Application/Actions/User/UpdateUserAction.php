<?php

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\User\SqlUserRepository;
use App\Shared\Sanitizer;
use Psr\Http\Message\ResponseInterface as Response;
use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\NestedValidationException;

class UpdateUserAction extends Action
{
    private SqlUserRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlUserRepository();
    }

    public static function rules(): array
    {
        return [
            'email' => v::stringType()->email()->length(5, 150)->optional(),
            'password' => v::stringType()->length(8, 255)->optional(),
            'role' => v::stringType()->in(['admin', 'moderator', 'user'])->optional(),
            'is_active' => v::boolType()->optional(),
        ];
    }

    protected function action(): Response
    {
        try {
            $userId = (int) $this->args['id'];

            // Verificar que el usuario existe
            $user = $this->repository->findById($userId);
            if (!$user) {
                return $this->respondWithError('User not found', 404);
            }

            $formData = $this->getFormData();
            
            if (empty($formData)) {
                $formData = $this->getJsonData();
            }

            if (empty($formData)) {
                return $this->respondWithError('No data to update', 400);
            }

            // Sanitizar datos
            $sanitizationRules = [
                'email' => 'email',
                'password' => 'string',
                'role' => 'string',
            ];
            $formData = Sanitizer::sanitizeArray($formData, $sanitizationRules);

            // Validar seguridad
            if (isset($formData['email']) && !Sanitizer::isSafe($formData['email'])) {
                error_log("Potential security threat in email field - User: {$userId}, IP: " . $this->getClientIp());
                return $this->respondWithError('Invalid data detected', 400);
            }

            $errors = $this->validate($formData);
            if (!empty($errors)) {
                return $this->respondWithError('Validation error', 422, $errors);
            }

            // Preparar datos para actualizar
            $updateData = [];

            if (isset($formData['email'])) {
                // Verificar que el email no exista en otro usuario
                $existingEmail = $this->repository->findByEmail($formData['email']);
                if ($existingEmail && $existingEmail->getId() !== $userId) {
                    return $this->respondWithError('Email already in use', 409);
                }
                $updateData['email'] = $formData['email'];
            }

            if (isset($formData['password'])) {
                $updateData['password'] = password_hash($formData['password'], PASSWORD_BCRYPT, ['cost' => 10]);
                $updateData['password_changed_at'] = date('Y-m-d H:i:s');
            }

            if (isset($formData['role'])) {
                $updateData['role'] = $formData['role'];
            }

            if (isset($formData['is_active'])) {
                $updateData['is_active'] = $formData['is_active'];
            }

            // Actualizar usuario
            $updatedUser = $this->repository->update($userId, $updateData);

            error_log("User updated - ID: {$userId}, IP: " . $this->getClientIp());

            return $this->respondWithData([
                'id' => $updatedUser->getId(),
                'username' => $updatedUser->getUsername(),
                'email' => $updatedUser->getEmail(),
                'role' => $updatedUser->getRole(),
                'is_active' => $updatedUser->isActive(),
                'updated_at' => $updatedUser->getUpdatedAt()
            ], 200);
        } catch (\Exception $e) {
            error_log("Error updating user: " . $e->getMessage());
            return $this->respondWithError('Error updating user', 500);
        }
    }

    private function validate(array $data): array
    {
        $errors = [];

        try {
            v::keySet(
                v::key('email', v::stringType()->email()->length(5, 150), true),
                v::key('password', v::stringType()->length(8, 255), true),
                v::key('role', v::stringType()->in(['admin', 'moderator', 'user']), true),
                v::key('is_active', v::boolType(), true)
            )->assert($data);
        } catch (NestedValidationException $e) {
            $errors = $e->getMessages();
        }

        return $errors;
    }
}
