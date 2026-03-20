<?php

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\User\SqlUserRepository;
use Psr\Http\Message\ResponseInterface as Response;

class DeleteUserAction extends Action
{
    private SqlUserRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlUserRepository();
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

            // Soft delete
            $result = $this->repository->delete($userId);

            if (!$result) {
                return $this->respondWithError('Error deleting user', 500);
            }

            error_log("User deleted (soft delete) - ID: {$userId}, IP: " . $this->getClientIp());

            return $this->respondWithData([], 200, 'User deleted successfully');
        } catch (\Exception $e) {
            error_log("Error deleting user: " . $e->getMessage());
            return $this->respondWithError('Error deleting user', 500);
        }
    }
}
