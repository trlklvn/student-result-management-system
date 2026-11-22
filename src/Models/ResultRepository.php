<?php
declare(strict_types=1);

namespace App\Models;

final class ResultRepository extends Repository
{
    public function findByEnrollment(int $enrollmentId): ?array
    {
        return $this->one('SELECT * FROM results WHERE enrollment_id = ?', [$enrollmentId]);
    }

    /** Insert or update the result row for one enrollment. */
    public function save(int $enrollmentId, float $ca, float $exam, float $total, string $grade, float $gp, int $userId): void
    {
        $params = [$ca, $exam, $total, $grade, $gp, $userId, $this->now(), $enrollmentId];
        if ($this->findByEnrollment($enrollmentId)) {
            $this->run(
                "UPDATE results SET ca_score = ?, exam_score = ?, total = ?, grade = ?, grade_point = ?,
                        status = 'draft', updated_by = ?, updated_at = ? WHERE enrollment_id = ?",
                $params
            );
        } else {
            $this->run(
                "INSERT INTO results (ca_score, exam_score, total, grade, grade_point, updated_by, updated_at, enrollment_id, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'draft')",
                $params
            );
        }
    }

    /** Move every result of a course from one status to the next. Returns rows changed. */
    public function changeStatusForCourse(int $courseId, string $from, string $to, int $userId): int
    {
        return $this->run(
            'UPDATE results SET status = ?, updated_by = ?, updated_at = ?
             WHERE status = ? AND enrollment_id IN (SELECT id FROM enrollments WHERE course_id = ?)',
            [$to, $userId, $this->now(), $from, $courseId]
        );
    }

    /** Status counts for one course, e.g. ['draft' => 2, 'missing' => 1]. */
    public function statusSummary(int $courseId): array
    {
        $rows = $this->all(
            "SELECT COALESCE(r.status, 'missing') AS status, COUNT(*) AS n
             FROM enrollments e LEFT JOIN results r ON r.enrollment_id = e.id
             WHERE e.course_id = ? GROUP BY COALESCE(r.status, 'missing')",
            [$courseId]
        );
        $summary = ['missing' => 0, 'draft' => 0, 'submitted' => 0, 'published' => 0];
        foreach ($rows as $row) {
            $summary[$row['status']] = (int) $row['n'];
        }
        return $summary;
    }

    /** Published results of one student, oldest semester first. */
    public function publishedForStudent(int $studentId): array
    {
        return $this->all(
            "SELECT c.code, c.title, c.credit_hours, c.semester, c.academic_year,
                    r.ca_score, r.exam_score, r.total, r.grade, r.grade_point
             FROM results r
             JOIN enrollments e ON e.id = r.enrollment_id
             JOIN courses c ON c.id = e.course_id
             WHERE e.student_id = ? AND r.status = 'published'
             ORDER BY c.academic_year, c.semester, c.code",
            [$studentId]
        );
    }

    public function countByStatus(string $status): int
    {
        return (int) ($this->one('SELECT COUNT(*) AS n FROM results WHERE status = ?', [$status])['n'] ?? 0);
    }
}
