<?php

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\User\SqlUserRepository;
use App\Domain\User\UserNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;

class ViewUserAction extends Action
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

            $user = $this->repository->findById($userId);

            if (!$user) {
                return $this->respondWithError('User not found', 404);
            }

            error_log("View user action - User ID: {$userId}, IP: " . $this->getClientIp());

            return $this->respondWithData([
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'email' => $user->getEmail(),
                'role' => $user->getRole(),
                'is_active' => $user->isActive(),
                'status_id' => $user->getStatusId(),
                'last_login_at' => $user->getLastLoginAt(),
                'last_ip_address' => $user->getLastIpAddress(),
                'failed_attempts' => $user->getFailedAttempts(),
                'block_until' => $user->getBlockUntil(),
                'created_at' => $user->getCreatedAt(),
                'updated_at' => $user->getUpdatedAt()
            ], 200);
        } catch (\Exception $e) {
            error_log("Error viewing user: " . $e->getMessage());
            return $this->respondWithError('Error viewing user', 500);
        }
    }
}
