<?php

namespace Sentinel\Models;

use Sentinel\Core\Auth;
use Sentinel\Core\Database;

class AdminUser
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function create(string $email, string $password, string $displayName): int
    {
        return $this->db->insert('admin_users', [
            'email'         => strtolower(trim($email)),
            'password_hash' => Auth::hashPassword($password),
            'display_name'  => trim($displayName),
        ]);
    }

    public function findByEmail(string $email): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM admin_users WHERE email = :email AND is_active = TRUE',
            ['email' => strtolower(trim($email))]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->db->queryOne(
            'SELECT * FROM admin_users WHERE id = :id',
            ['id' => $id]
        );
    }

    public function count(): int
    {
        return (int) $this->db->queryScalar('SELECT COUNT(*) FROM admin_users');
    }

    public function updateLastLogin(int $id): void
    {
        $this->db->execute(
            'UPDATE admin_users SET last_login_at = NOW() WHERE id = :id',
            ['id' => $id]
        );
    }

    public function updatePassword(string $email, string $newPassword): bool
    {
        $user = $this->findByEmail($email);
        if (!$user) {
            return false;
        }

        $this->db->execute(
            'UPDATE admin_users SET password_hash = :hash WHERE id = :id',
            [
                'hash' => Auth::hashPassword($newPassword),
                'id'   => $user['id'],
            ]
        );

        return true;
    }
}
