<?php
declare(strict_types=1);

namespace App\Models;

final class StudentRepository extends Repository
{
    public function create(int $userId, string $indexNumber, string $programme, int $level): int
    {
        return $this->insert(
            'INSERT INTO students (user_id, index_number, programme, level) VALUES (?, ?, ?, ?)',
            [$userId, $indexNumber, $programme, $level]
        );
    }

    public function listAll(): array
    {
        return $this->all(
            'SELECT s.*, u.full_name, u.username FROM students s JOIN users u ON u.id = s.user_id ORDER BY s.index_number'
        );
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->one(
            'SELECT s.*, u.full_name FROM students s JOIN users u ON u.id = s.user_id WHERE s.user_id = ?',
            [$userId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->one(
            'SELECT s.*, u.full_name FROM students s JOIN users u ON u.id = s.user_id WHERE s.id = ?',
            [$id]
        );
    }

    public function indexExists(string $indexNumber): bool
    {
        return $this->one('SELECT id FROM students WHERE index_number = ?', [$indexNumber]) !== null;
    }
}
