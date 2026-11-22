<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\UserRepository;

/** Session-based authentication and role-based access control. */
final class Auth
{
    public static function attempt(string $username, string $password): bool
    {
        $user = (new UserRepository())->findByUsername($username);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'full_name' => $user['full_name'],
            'role' => $user['role'],
        ];
        return true;
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return self::user()['id'] ?? null;
    }

    public static function hasRole(string $role): bool
    {
        return (self::user()['role'] ?? null) === $role;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }
}
