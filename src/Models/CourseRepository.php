<?php
declare(strict_types=1);

namespace App\Models;

final class CourseRepository extends Repository
{
    public function create(string $code, string $title, int $credits, int $semester, string $year, ?int $lecturerId): int
    {
        return $this->insert(
            'INSERT INTO courses (code, title, credit_hours, semester, academic_year, lecturer_id) VALUES (?, ?, ?, ?, ?, ?)',
            [strtoupper($code), $title, $credits, $semester, $year, $lecturerId]
        );
    }

    /** Courses with lecturer name and the overall result status for each course. */
    public function listAll(): array
    {
        return $this->all(
            "SELECT c.*, u.full_name AS lecturer_name,
                    (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS enrolled,
                    (SELECT COUNT(*) FROM enrollments e JOIN results r ON r.enrollment_id = e.id
                       WHERE e.course_id = c.id AND r.status = 'submitted') AS submitted,
                    (SELECT COUNT(*) FROM enrollments e JOIN results r ON r.enrollment_id = e.id
                       WHERE e.course_id = c.id AND r.status = 'published') AS published
             FROM courses c LEFT JOIN users u ON u.id = c.lecturer_id
             ORDER BY c.academic_year, c.semester, c.code"
        );
    }

    public function byLecturer(int $lecturerId): array
    {
        return $this->all(
            "SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS enrolled
             FROM courses c WHERE c.lecturer_id = ? ORDER BY c.code",
            [$lecturerId]
        );
    }

    public function find(int $id): ?array
    {
        return $this->one('SELECT * FROM courses WHERE id = ?', [$id]);
    }

    public function codeExists(string $code): bool
    {
        return $this->one('SELECT id FROM courses WHERE code = ?', [strtoupper($code)]) !== null;
    }
}
