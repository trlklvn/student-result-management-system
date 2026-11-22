<?php
declare(strict_types=1);

namespace App\Services;

use App\Models\AuditRepository;
use App\Models\CourseRepository;
use App\Models\EnrollmentRepository;
use App\Models\ResultRepository;

/**
 * Business logic for results (Service layer).
 * Result life cycle:  (none) -> draft -> submitted -> published
 *   - lecturers create/edit drafts and submit them
 *   - the admin publishes submitted results (or returns them to draft)
 *   - students only ever see published results
 */
final class ResultService
{
    public function __construct(
        private GradingScheme $scheme = new StandardGradingScheme(),
        private ?ResultRepository $results = null,
        private ?EnrollmentRepository $enrollments = null,
        private ?CourseRepository $courses = null,
        private ?AuditRepository $audit = null,
    ) {
        $this->results ??= new ResultRepository();
        $this->enrollments ??= new EnrollmentRepository();
        $this->courses ??= new CourseRepository();
        $this->audit ??= new AuditRepository();
    }

    /** Validate CA and exam scores and return the computed total, grade and grade point. */
    public function compute(float $ca, float $exam): array
    {
        $maxCa = (float) (config('max_ca') ?? 40);
        $maxExam = (float) (config('max_exam') ?? 60);
        if ($ca < 0 || $ca > $maxCa) {
            throw new ValidationException("CA score must be between 0 and {$maxCa}.");
        }
        if ($exam < 0 || $exam > $maxExam) {
            throw new ValidationException("Exam score must be between 0 and {$maxExam}.");
        }
        $total = round($ca + $exam, 2);
        $g = $this->scheme->grade($total);
        return ['total' => $total, 'grade' => $g['grade'], 'point' => $g['point']];
    }

    /**
     * Save scores for a lecturer's course.
     * @param array<int, array{ca:string|float, exam:string|float}> $scores keyed by enrollment id
     * @return int number of results saved
     */
    public function recordScores(int $lecturerId, int $courseId, array $scores): int
    {
        $course = $this->ownedCourse($lecturerId, $courseId);
        $saved = 0;
        foreach ($scores as $enrollmentId => $row) {
            $ca = trim((string) ($row['ca'] ?? ''));
            $exam = trim((string) ($row['exam'] ?? ''));
            if ($ca === '' && $exam === '') {
                continue; // left blank: nothing to save yet
            }
            if (!is_numeric($ca) || !is_numeric($exam)) {
                throw new ValidationException('Enter both a CA and an exam score as numbers, or leave both blank.');
            }
            $enrollmentId = (int) $enrollmentId;
            if (!$this->enrollments->belongsToCourse($enrollmentId, $courseId)) {
                throw new ValidationException('One of the students is not enrolled in this course.');
            }
            $existing = $this->results->findByEnrollment($enrollmentId);
            if ($existing && $existing['status'] !== 'draft') {
                throw new ValidationException('Results that are submitted or published cannot be edited.');
            }
            $c = $this->compute((float) $ca, (float) $exam);
            $this->results->save($enrollmentId, (float) $ca, (float) $exam, $c['total'], $c['grade'], $c['point'], $lecturerId);
            $saved++;
        }
        $this->audit->log($lecturerId, 'scores_saved', "{$saved} result(s) saved for {$course['code']}");
        return $saved;
    }

    /** Lecturer submits all draft results of a course for approval. Every student must have a score. */
    public function submit(int $lecturerId, int $courseId): int
    {
        $course = $this->ownedCourse($lecturerId, $courseId);
        $summary = $this->results->statusSummary($courseId);
        if ($summary['missing'] > 0) {
            throw new ValidationException("{$summary['missing']} student(s) still have no score. Enter all scores before submitting.");
        }
        if ($summary['draft'] === 0) {
            throw new ValidationException('There are no draft results to submit.');
        }
        $n = $this->results->changeStatusForCourse($courseId, 'draft', 'submitted', $lecturerId);
        $this->audit->log($lecturerId, 'results_submitted', "{$n} result(s) submitted for {$course['code']}");
        return $n;
    }

    /** Admin publishes submitted results so students can see them. */
    public function publish(int $adminId, int $courseId): int
    {
        $course = $this->courses->find($courseId) ?? throw new ValidationException('Course not found.');
        $n = $this->results->changeStatusForCourse($courseId, 'submitted', 'published', $adminId);
        if ($n === 0) {
            throw new ValidationException("{$course['code']} has no submitted results to publish.");
        }
        $this->audit->log($adminId, 'results_published', "{$n} result(s) published for {$course['code']}");
        return $n;
    }

    /** Admin sends submitted results back to the lecturer for correction. */
    public function returnToDraft(int $adminId, int $courseId): int
    {
        $course = $this->courses->find($courseId) ?? throw new ValidationException('Course not found.');
        $n = $this->results->changeStatusForCourse($courseId, 'submitted', 'draft', $adminId);
        if ($n === 0) {
            throw new ValidationException("{$course['code']} has no submitted results to return.");
        }
        $this->audit->log($adminId, 'results_returned', "{$n} result(s) returned to lecturer for {$course['code']}");
        return $n;
    }

    /**
     * Build a transcript: results grouped by semester with GPA, plus CGPA and class.
     * GPA = sum(grade point x credit hours) / sum(credit hours)
     */
    public function transcript(int $studentId): array
    {
        $rows = $this->results->publishedForStudent($studentId);
        $semesters = [];
        foreach ($rows as $r) {
            $key = $r['academic_year'] . ' Sem ' . $r['semester'];
            $semesters[$key]['courses'][] = $r;
        }
        $allPoints = 0.0;
        $allCredits = 0;
        foreach ($semesters as $key => $sem) {
            [$gpa, $points, $credits] = self::gpa($sem['courses']);
            $semesters[$key]['gpa'] = $gpa;
            $semesters[$key]['credits'] = $credits;
            $allPoints += $points;
            $allCredits += $credits;
        }
        $cgpa = $allCredits > 0 ? round($allPoints / $allCredits, 2) : 0.0;
        return [
            'semesters' => $semesters,
            'cgpa' => $cgpa,
            'credits' => $allCredits,
            'class' => $allCredits > 0 ? $this->scheme->classify($cgpa) : '—',
        ];
    }

    /** @return array{0:float,1:float,2:int} [gpa, weighted points, credits] */
    public static function gpa(array $courses): array
    {
        $points = 0.0;
        $credits = 0;
        foreach ($courses as $c) {
            $points += (float) $c['grade_point'] * (int) $c['credit_hours'];
            $credits += (int) $c['credit_hours'];
        }
        return [$credits > 0 ? round($points / $credits, 2) : 0.0, $points, $credits];
    }

    private function ownedCourse(int $lecturerId, int $courseId): array
    {
        $course = $this->courses->find($courseId);
        if (!$course || (int) $course['lecturer_id'] !== $lecturerId) {
            throw new ValidationException('You can only manage results for courses assigned to you.');
        }
        return $course;
    }
}
