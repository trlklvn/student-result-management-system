<?php
declare(strict_types=1);

namespace App\Models;

final class AuditRepository extends Repository
{
    public function log(?int $userId, string $action, string $details): void
    {
        $this->run(
            'INSERT INTO audit_log (user_id, action, details, created_at) VALUES (?, ?, ?, ?)',
            [$userId, $action, mb_substr($details, 0, 255), $this->now()]
        );
    }

    public function recent(int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        return $this->all(
            "SELECT a.*, u.full_name FROM audit_log a LEFT JOIN users u ON u.id = a.user_id
             ORDER BY a.id DESC LIMIT $limit"
        );
    }
}
