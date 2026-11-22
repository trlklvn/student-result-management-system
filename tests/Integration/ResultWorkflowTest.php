<?php
declare(strict_types=1);

use App\Models\CourseRepository;
use App\Models\EnrollmentRepository;
use App\Models\ResultRepository;
use App\Models\StudentRepository;
use App\Models\UserRepository;
use App\Services\ResultService;
use App\Services\ValidationException;

/** Service + repositories + real SQL schema working together. */
final class ResultWorkflowTest extends TestCase
{
    private ResultService $svc;
    private int $admin, $lecturer, $other, $course, $studentA, $studentB, $eA, $eB;

    public function setUp(): void
    {
        $this->freshDatabase();
        $users = new UserRepository();
        $this->admin = $users->create('admin', 'admin123', 'Admin', 'admin');
        $this->lecturer = $users->create('lec', 'lecturer123', 'Lecturer One', 'lecturer');
        $this->other = $users->create('lec2', 'lecturer123', 'Lecturer Two', 'lecturer');
        $students = new StudentRepository();
        $this->studentA = $students->create($users->create('S001', 'pw1234', 'Student A', 'student'), 'S001', 'BSc CS', 200);
        $this->studentB = $students->create($users->create('S002', 'pw1234', 'Student B', 'student'), 'S002', 'BSc CS', 200);
        $this->course = (new CourseRepository())->create('CSC 201', 'DSA', 3, 1, '2025/2026', $this->lecturer);
        $enr = new EnrollmentRepository();
        $this->eA = $enr->enroll($this->studentA, $this->course);
        $this->eB = $enr->enroll($this->studentB, $this->course);
        $this->svc = new ResultService();
    }

    /** IT-01 Saving scores stores total, grade and draft status in the database */
    public function testRecordScoresPersists(): void
    {
        $n = $this->svc->recordScores($this->lecturer, $this->course, [$this->eA => ['ca' => '35', 'exam' => '50']]);
        $this->assertSame(1, $n);
        $row = (new ResultRepository())->findByEnrollment($this->eA);
        $this->assertSame('A', $row['grade']);
        $this->assertSame('draft', $row['status']);
        $this->assertSame(85.0, (float) $row['total']);
    }

    /** IT-02 A lecturer cannot enter scores for a course assigned to someone else */
    public function testOtherLecturerBlocked(): void
    {
        $this->assertThrows(ValidationException::class,
            fn() => $this->svc->recordScores($this->other, $this->course, [$this->eA => ['ca' => 30, 'exam' => 40]]),
            'assigned to you');
    }

    /** IT-03 Submitting is refused while any student has no score */
    public function testSubmitNeedsAllScores(): void
    {
        $this->svc->recordScores($this->lecturer, $this->course, [$this->eA => ['ca' => 30, 'exam' => 40]]);
        $this->assertThrows(ValidationException::class, fn() => $this->svc->submit($this->lecturer, $this->course), 'still have no score');
    }

    /** IT-04 Full life cycle draft -> submitted -> published, and locked after submission */
    public function testFullLifecycle(): void
    {
        $this->svc->recordScores($this->lecturer, $this->course, [
            $this->eA => ['ca' => 36, 'exam' => 52], $this->eB => ['ca' => 20, 'exam' => 25],
        ]);
        $this->assertSame(2, $this->svc->submit($this->lecturer, $this->course));
        $this->assertThrows(ValidationException::class,
            fn() => $this->svc->recordScores($this->lecturer, $this->course, [$this->eA => ['ca' => 10, 'exam' => 10]]),
            'cannot be edited');
        $this->assertSame(2, $this->svc->publish($this->admin, $this->course));
        $summary = (new ResultRepository())->statusSummary($this->course);
        $this->assertSame(2, $summary['published']);
    }

    /** IT-05 Students see only published results; transcript computes GPA and class */
    public function testTranscriptOnlyPublished(): void
    {
        $this->svc->recordScores($this->lecturer, $this->course, [
            $this->eA => ['ca' => 36, 'exam' => 52], $this->eB => ['ca' => 20, 'exam' => 25],
        ]);
        $this->assertSame([], $this->svc->transcript($this->studentA)['semesters'], 'draft must be hidden');
        $this->svc->submit($this->lecturer, $this->course);
        $this->svc->publish($this->admin, $this->course);
        $t = $this->svc->transcript($this->studentA);
        $this->assertSame(4.0, $t['cgpa']);
        $this->assertSame('First Class', $t['class']);
        $this->assertSame(3, $t['credits']);
        $this->assertSame('F', $this->svc->transcript($this->studentB)['semesters']['2025/2026 Sem 1']['courses'][0]['grade']);
    }

    /** IT-06 Admin can return submitted results; they become editable drafts again */
    public function testReturnToDraft(): void
    {
        $this->svc->recordScores($this->lecturer, $this->course, [
            $this->eA => ['ca' => 30, 'exam' => 40], $this->eB => ['ca' => 30, 'exam' => 40],
        ]);
        $this->svc->submit($this->lecturer, $this->course);
        $this->assertSame(2, $this->svc->returnToDraft($this->admin, $this->course));
        $this->assertSame(1, $this->svc->recordScores($this->lecturer, $this->course, [$this->eA => ['ca' => 31, 'exam' => 40]]));
    }

    /** IT-07 Database rejects duplicate enrolment and duplicate index numbers */
    public function testConstraints(): void
    {
        $this->assertSame($this->eA, (new EnrollmentRepository())->enroll($this->studentA, $this->course), 'enroll is idempotent');
        $this->assertTrue((new StudentRepository())->indexExists('S001'));
        $this->assertThrows(PDOException::class, fn() => (new UserRepository())->create('S001', 'x123456', 'Dup', 'student'));
    }

    /** IT-09 Course codes are normalised so "csc201" duplicates "CSC 201" (regression test for DEF-01) */
    public function testCourseCodeNormalised(): void
    {
        $repo = new CourseRepository();
        $this->assertSame('CSC 201', CourseRepository::normalizeCode('csc201'));
        $this->assertSame('CSC 201', CourseRepository::normalizeCode(' CSC   201 '));
        $this->assertTrue($repo->codeExists('CSC201'), 'CSC201 must be seen as the existing CSC 201');
    }

    /** IT-08 Every result change is written to the audit log */
    public function testAuditTrail(): void
    {
        $this->svc->recordScores($this->lecturer, $this->course, [$this->eA => ['ca' => 30, 'exam' => 40]]);
        $log = (new \App\Models\AuditRepository())->recent(5);
        $this->assertSame('scores_saved', $log[0]['action']);
        $this->assertContains('CSC 201', $log[0]['details']);
    }
}
