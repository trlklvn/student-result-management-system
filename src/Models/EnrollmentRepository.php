<?php
declare(strict_types=1);

namespace App\Models;

final class EnrollmentRepository extends Repository
{
    public function enroll(int $studentId, int $courseId): int
    {
        $existing = $this->one('SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?', [$studentId, $courseId]);
        if ($existing) {
            return (int) $existing['id'];
        }
        return $this->insert('INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)', [$studentId, $courseId]);
    }

    /** Class list for a course with any recorded result. */
    public function forCourse(int $courseId): array
    {
        return $this->all(
            'SELECT e.id AS enrollment_id, s.index_number, u.full_name,
                    r.ca_score, r.exam_score, r.total, r.grade, r.grade_point, r.status
             FROM enrollments e
             JOIN students s ON s.id = e.student_id
             JOIN users u ON u.id = s.user_id
             LEFT JOIN results r ON r.enrollment_id = e.id
             WHERE e.course_id = ?
             ORDER BY s.index_number',
            [$courseId]
        );
    }

    public function belongsToCourse(int $enrollmentId, int $courseId): bool
    {
        return $this->one('SELECT id FROM enrollments WHERE id = ? AND course_id = ?', [$enrollmentId, $courseId]) !== null;
    }
}
