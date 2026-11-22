<?php
declare(strict_types=1);

namespace App\Models;

final class UserRepository extends Repository
{
    public function findByUsername(string $username): ?array
    {
        return $this->one('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public function findById(int $id): ?array
    {
        return $this->one('SELECT id, username, full_name, role FROM users WHERE id = ?', [$id]);
    }

    public function create(string $username, string $password, string $fullName, string $role): int
    {
        return $this->insert(
            'INSERT INTO users (username, password_hash, full_name, role, created_at) VALUES (?, ?, ?, ?, ?)',
            [$username, password_hash($password, PASSWORD_DEFAULT), $fullName, $role, $this->now()]
        );
    }

    public function lecturers(): array
    {
        return $this->all("SELECT id, username, full_name FROM users WHERE role = 'lecturer' ORDER BY full_name");
    }

    public function countByRole(string $role): int
    {
        return (int) ($this->one('SELECT COUNT(*) AS n FROM users WHERE role = ?', [$role])['n'] ?? 0);
    }
}
