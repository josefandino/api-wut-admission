<?php

namespace App\Domain\User;

class User
{
    private int $id;
    private string $username;
    private string $email;
    private string $passwordHash;
    private bool $isActive;
    private ?string $blockUntil;
    private int $failedAttempts;
    private ?string $lastIpAddress;
    private ?string $lastUserAgent;
    private int $statusId;
    private string $role;
    private ?string $lastLoginAt;
    private ?string $passwordChangedAt;
    private ?string $loginAttemptsWindow;
    private ?string $accountLockedUntil;
    private string $createdAt;
    private string $updatedAt;
    private ?string $deletedAt;

    public function __construct(
        int $id,
        string $username,
        string $email,
        string $passwordHash,
        bool $isActive,
        ?string $blockUntil,
        int $failedAttempts,
        ?string $lastIpAddress,
        ?string $lastUserAgent,
        int $statusId,
        string $role,
        ?string $lastLoginAt,
        ?string $passwordChangedAt,
        ?string $loginAttemptsWindow,
        ?string $accountLockedUntil,
        string $createdAt,
        string $updatedAt,
        ?string $deletedAt = null
    ) {
        $this->id = $id;
        $this->username = $username;
        $this->email = $email;
        $this->passwordHash = $passwordHash;
        $this->isActive = $isActive;
        $this->blockUntil = $blockUntil;
        $this->failedAttempts = $failedAttempts;
        $this->lastIpAddress = $lastIpAddress;
        $this->lastUserAgent = $lastUserAgent;
        $this->statusId = $statusId;
        $this->role = $role;
        $this->lastLoginAt = $lastLoginAt;
        $this->passwordChangedAt = $passwordChangedAt;
        $this->loginAttemptsWindow = $loginAttemptsWindow;
        $this->accountLockedUntil = $accountLockedUntil;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
        $this->deletedAt = $deletedAt;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function getPasswordHash(): string
    {
        return $this->passwordHash;
    }

    public function isActive(): bool
    {
        return $this->isActive;
    }

    public function getBlockUntil(): ?string
    {
        return $this->blockUntil;
    }

    public function getFailedAttempts(): int
    {
        return $this->failedAttempts;
    }

    public function getLastIpAddress(): ?string
    {
        return $this->lastIpAddress;
    }

    public function getLastUserAgent(): ?string
    {
        return $this->lastUserAgent;
    }

    public function getStatusId(): int
    {
        return $this->statusId;
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function getLastLoginAt(): ?string
    {
        return $this->lastLoginAt;
    }

    public function getPasswordChangedAt(): ?string
    {
        return $this->passwordChangedAt;
    }

    public function getLoginAttemptsWindow(): ?string
    {
        return $this->loginAttemptsWindow;
    }

    public function getAccountLockedUntil(): ?string
    {
        return $this->accountLockedUntil;
    }

    public function getCreatedAt(): string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): string
    {
        return $this->updatedAt;
    }

    public function getDeletedAt(): ?string
    {
        return $this->deletedAt;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'username' => $this->username,
            'email' => $this->email,
            'is_active' => $this->isActive,
            'block_until' => $this->blockUntil,
            'failed_attempts' => $this->failedAttempts,
            'last_ip_address' => $this->lastIpAddress,
            'last_user_agent' => $this->lastUserAgent,
            'status_id' => $this->statusId,
            'role' => $this->role,
            'last_login_at' => $this->lastLoginAt,
            'password_changed_at' => $this->passwordChangedAt,
            'login_attempts_window' => $this->loginAttemptsWindow,
            'account_locked_until' => $this->accountLockedUntil,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt
        ];
    }
}
