<?php

namespace App\Infrastructure\Persistence\User;

use App\Domain\User\User;
use App\Domain\User\UserRepository;
use App\Shared\Database;
use PDO;
use DateTime;

class SqlUserRepository implements UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function create(
        string $username,
        string $email,
        string $passwordHash,
        string $role = 'user',
        string $lastIpAddress = '',
        string $lastUserAgent = ''
    ): User {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (username, email, password_hash, role, last_ip_address, last_user_agent, is_active, status_id, failed_attempts, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, TRUE, 1, 0, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
        );
        $stmt->execute([$username, $email, $passwordHash, $role, $lastIpAddress, $lastUserAgent]);

        $id = (int) $this->pdo->lastInsertId();

        return new User(
            $id,
            $username,
            $email,
            $passwordHash,
            true,
            null,
            0,
            $lastIpAddress ?: null,
            $lastUserAgent ?: null,
            1,
            $role,
            null,
            null,
            null,
            null,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s'),
            null
        );
    }

    public function findById(int $id): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ? AND deleted_at IS NULL');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapToUser($row) : null;
    }

    public function findByUsername(string $username): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE username = ? AND deleted_at IS NULL');
        $stmt->execute([$username]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapToUser($row) : null;
    }

    public function findByEmail(string $email): ?User
    {
        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE email = ? AND deleted_at IS NULL');
        $stmt->execute([$email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? $this->mapToUser($row) : null;
    }

    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT * FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC');
        $users = [];

        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $users[] = $this->mapToUser($row);
        }

        return $users;
    }

    public function update(int $id, array $data): ?User
    {
        $user = $this->findById($id);
        if (!$user) {
            return null;
        }

        $updates = [];
        $values = [];

        foreach ($data as $key => $value) {
            // Convertir camelCase a snake_case
            $dbKey = preg_replace('/([A-Z])/', '_$1', lcfirst($key));
            $dbKey = strtolower($dbKey);

            if ($dbKey === 'password') {
                $dbKey = 'password_hash';
            }

            $updates[] = "{$dbKey} = ?";
            $values[] = $value;
        }

        if (empty($updates)) {
            return $user;
        }

        $values[] = $id;
        $sql = 'UPDATE users SET ' . implode(', ', $updates) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ?';
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);

        return $this->findById($id);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET deleted_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE id = ?');
        return $stmt->execute([$id]);
    }

    public function recordFailedAttempt(int $userId, string $ipAddress, string $userAgent): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET failed_attempts = failed_attempts + 1, last_ip_address = ?, last_user_agent = ?, login_attempts_window = CURRENT_TIMESTAMP WHERE id = ?'
        );
        return $stmt->execute([$ipAddress, $userAgent, $userId]);
    }

    public function recordSuccessfulLogin(int $userId, string $ipAddress, string $userAgent): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE users SET failed_attempts = 0, last_login_at = CURRENT_TIMESTAMP, last_ip_address = ?, last_user_agent = ? WHERE id = ?'
        );
        return $stmt->execute([$ipAddress, $userAgent, $userId]);
    }

    public function resetFailedAttempts(int $userId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET failed_attempts = 0 WHERE id = ?');
        return $stmt->execute([$userId]);
    }

    public function blockUser(int $userId, int $minutes = 30): bool
    {
        $blockUntil = (new DateTime())->modify("+{$minutes} minutes")->format('Y-m-d H:i:s');
        $stmt = $this->pdo->prepare('UPDATE users SET block_until = ?, account_locked_until = ? WHERE id = ?');
        return $stmt->execute([$blockUntil, $blockUntil, $userId]);
    }

    public function unblockUser(int $userId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET block_until = NULL, account_locked_until = NULL, failed_attempts = 0 WHERE id = ?');
        return $stmt->execute([$userId]);
    }

    private function mapToUser(array $row): User
    {
        return new User(
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
    }
}
