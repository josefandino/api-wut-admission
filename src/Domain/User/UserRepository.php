<?php

namespace App\Domain\User;

interface UserRepository
{
    /**
     * Crear un nuevo usuario
     */
    public function create(
        string $username,
        string $email,
        string $passwordHash,
        string $role = 'user',
        string $lastIpAddress = '',
        string $lastUserAgent = ''
    ): User;

    /**
     * Obtener usuario por ID
     */
    public function findById(int $id): ?User;

    /**
     * Obtener usuario por username
     */
    public function findByUsername(string $username): ?User;

    /**
     * Obtener usuario por email
     */
    public function findByEmail(string $email): ?User;

    /**
     * Listar todos los usuarios (sin soft-deleted)
     */
    public function findAll(): array;

    /**
     * Actualizar usuario
     */
    public function update(
        int $id,
        array $data
    ): ?User;

    /**
     * Soft delete - marcar como eliminado
     */
    public function delete(int $id): bool;

    /**
     * Registrar intento fallido de login
     */
    public function recordFailedAttempt(int $userId, string $ipAddress, string $userAgent): bool;

    /**
     * Registrar login exitoso
     */
    public function recordSuccessfulLogin(int $userId, string $ipAddress, string $userAgent): bool;

    /**
     * Resetear intentos fallidos
     */
    public function resetFailedAttempts(int $userId): bool;

    /**
     * Bloquear usuario
     */
    public function blockUser(int $userId, int $minutes = 30): bool;

    /**
     * Desbloquear usuario
     */
    public function unblockUser(int $userId): bool;
}
