<?php

namespace App\Application\Actions\User;

use App\Application\Actions\Action;
use App\Infrastructure\Persistence\User\SqlUserRepository;
use Psr\Http\Message\ResponseInterface as Response;

class ListUsersAction extends Action
{
    private SqlUserRepository $repository;

    public function __construct()
    {
        $this->repository = new SqlUserRepository();
    }

    protected function action(): Response
    {
        try {
            $users = $this->repository->findAll();

            // Transformar usuarios para no exponer passwordHash
            $userData = array_map(function ($user) {
                return [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'email' => $user->getEmail(),
                    'role' => $user->getRole(),
                    'is_active' => $user->isActive(),
                    'status_id' => $user->getStatusId(),
                    'last_login_at' => $user->getLastLoginAt(),
                    'created_at' => $user->getCreatedAt()
                ];
            }, $users);

            error_log("List users action - Total users: " . count($users) . ", IP: " . $this->getClientIp());

            return $this->respondWithData($userData, 200);
        } catch (\Exception $e) {
            error_log("Error listing users: " . $e->getMessage());
            return $this->respondWithError('Error listing users', 500);
        }
    }
}
