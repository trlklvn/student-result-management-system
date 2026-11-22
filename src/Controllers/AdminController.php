<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Models\AuditRepository;
use App\Models\CourseRepository;
use App\Models\EnrollmentRepository;
use App\Models\ResultRepository;
use App\Models\StudentRepository;
use App\Models\UserRepository;
use App\Services\ResultService;
use App\Services\ValidationException;

final class AdminController extends Controller
{
    public function dashboard(): void
    {
        $results = new ResultRepository();
        $this->render('admin/dashboard', [
            'title' => 'Dashboard',
            'stats' => [
                'Students' => (new UserRepository())->countByRole('student'),
                'Lecturers' => (new UserRepository())->countByRole('lecturer'),
                'Awaiting approval' => $results->countByStatus('submitted'),
                'Published results' => $results->countByStatus('published'),
            ],
            'courses' => (new CourseRepository())->listAll(),
            'audit' => (new AuditRepository())->recent(10),
        ]);
    }

    public function students(): void
    {
        $students = new StudentRepository();
        if ($this->isPost()) {
            $this->verifyCsrf();
            try {
                $index = strtoupper(trim((string) ($_POST['index_number'] ?? '')));
                $name = trim((string) ($_POST['full_name'] ?? ''));
                $programme = trim((string) ($_POST['programme'] ?? ''));
                $level = (int) ($_POST['level'] ?? 0);
                $password = (string) ($_POST['password'] ?? '');
                if (!preg_match('/^[A-Z0-9\/-]{4,20}$/', $index)) {
                    throw new ValidationException('Index number must be 4–20 letters, digits, "/" or "-".');
                }
                if ($name === '' || $programme === '') {
                    throw new ValidationException('Enter the student name and programme.');
                }
                if (!in_array($level, [100, 200, 300, 400], true)) {
                    throw new ValidationException('Level must be 100, 200, 300 or 400.');
                }
                if (strlen($password) < 6) {
                    throw new ValidationException('Password must be at least 6 characters.');
                }
                if ($students->indexExists($index)) {
                    throw new ValidationException("A student with index number {$index} already exists.");
                }
                $db = Database::connection();
                $db->beginTransaction();
                $userId = (new UserRepository())->create($index, $password, $name, 'student');
                $students->create($userId, $index, $programme, $level);
                $db->commit();
                (new AuditRepository())->log(Auth::id(), 'student_added', "{$index} {$name}");
                $this->flash('success', "Student {$name} added. They sign in with index number {$index}.");
            } catch (ValidationException $e) {
                $this->flash('error', $e->getMessage());
            }
            $this->redirect('admin/students');
        }
        $this->render('admin/students', ['title' => 'Students', 'students' => $students->listAll()]);
    }

    public function courses(): void
    {
        $courses = new CourseRepository();
        $users = new UserRepository();
        if ($this->isPost()) {
            $this->verifyCsrf();
            try {
                $action = $_POST['action'] ?? '';
                if ($action === 'add_course') {
                    $code = strtoupper(trim((string) ($_POST['code'] ?? '')));
                    $title = trim((string) ($_POST['title_text'] ?? ''));
                    $credits = (int) ($_POST['credit_hours'] ?? 0);
                    $semester = (int) ($_POST['semester'] ?? 0);
                    $year = trim((string) ($_POST['academic_year'] ?? ''));
                    $lecturer = ($_POST['lecturer_id'] ?? '') === '' ? null : (int) $_POST['lecturer_id'];
                    if (!preg_match('/^[A-Z]{2,5}\s?\d{3}$/', $code)) {
                        throw new ValidationException('Course code should look like "CSC 201".');
                    }
                    if ($title === '' || $credits < 1 || $credits > 6 || !in_array($semester, [1, 2], true)) {
                        throw new ValidationException('Enter a title, 1–6 credit hours and semester 1 or 2.');
                    }
                    if (!preg_match('/^\d{4}\/\d{4}$/', $year)) {
                        throw new ValidationException('Academic year should look like "2025/2026".');
                    }
                    if ($courses->codeExists($code)) {
                        throw new ValidationException("Course {$code} already exists.");
                    }
                    $courses->create($code, $title, $credits, $semester, $year, $lecturer);
                    (new AuditRepository())->log(Auth::id(), 'course_added', "{$code} {$title}");
                    $this->flash('success', "Course {$code} added.");
                } elseif ($action === 'add_lecturer') {
                    $username = strtolower(trim((string) ($_POST['username'] ?? '')));
                    $name = trim((string) ($_POST['full_name'] ?? ''));
                    $password = (string) ($_POST['password'] ?? '');
                    if (!preg_match('/^[a-z0-9._]{3,30}$/', $username) || $name === '' || strlen($password) < 6) {
                        throw new ValidationException('Enter a username (3–30 lowercase letters or digits), a name and a password of 6+ characters.');
                    }
                    if ($users->findByUsername($username)) {
                        throw new ValidationException("Username {$username} is taken.");
                    }
                    $users->create($username, $password, $name, 'lecturer');
                    (new AuditRepository())->log(Auth::id(), 'lecturer_added', "{$username} {$name}");
                    $this->flash('success', "Lecturer {$name} added.");
                } elseif ($action === 'enroll') {
                    $courseId = (int) ($_POST['course_id'] ?? 0);
                    $studentIds = array_map('intval', (array) ($_POST['student_ids'] ?? []));
                    if (!$courses->find($courseId) || $studentIds === []) {
                        throw new ValidationException('Choose a course and at least one student.');
                    }
                    $enrollments = new EnrollmentRepository();
                    foreach ($studentIds as $sid) {
                        $enrollments->enroll($sid, $courseId);
                    }
                    (new AuditRepository())->log(Auth::id(), 'students_enrolled', count($studentIds) . " student(s) enrolled in course #{$courseId}");
                    $this->flash('success', count($studentIds) . ' student(s) enrolled.');
                }
            } catch (ValidationException $e) {
                $this->flash('error', $e->getMessage());
            }
            $this->redirect('admin/courses');
        }
        $this->render('admin/courses', [
            'title' => 'Courses',
            'courses' => $courses->listAll(),
            'lecturers' => $users->lecturers(),
            'students' => (new StudentRepository())->listAll(),
        ]);
    }

    public function review(): void
    {
        $courseId = (int) ($_GET['course'] ?? $_POST['course_id'] ?? 0);
        $course = (new CourseRepository())->find($courseId);
        if (!$course) {
            $this->flash('error', 'Course not found.');
            $this->redirect('admin/dashboard');
        }
        if ($this->isPost()) {
            $this->verifyCsrf();
            $service = new ResultService();
            try {
                if (($_POST['action'] ?? '') === 'publish') {
                    $n = $service->publish(Auth::id(), $courseId);
                    $this->flash('success', "{$n} result(s) published. Students can now see them.");
                } else {
                    $n = $service->returnToDraft(Auth::id(), $courseId);
                    $this->flash('success', "{$n} result(s) returned to the lecturer for correction.");
                }
            } catch (ValidationException $e) {
                $this->flash('error', $e->getMessage());
            }
            $this->redirect('admin/review&course=' . $courseId);
        }
        $this->render('admin/review', [
            'title' => 'Review ' . $course['code'],
            'course' => $course,
            'rows' => (new EnrollmentRepository())->forCourse($courseId),
            'summary' => (new ResultRepository())->statusSummary($courseId),
        ]);
    }
}
