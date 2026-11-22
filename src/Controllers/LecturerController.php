<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\CourseRepository;
use App\Models\EnrollmentRepository;
use App\Models\ResultRepository;
use App\Services\ResultService;
use App\Services\ValidationException;

final class LecturerController extends Controller
{
    public function courses(): void
    {
        $courses = (new CourseRepository())->byLecturer(Auth::id());
        $results = new ResultRepository();
        foreach ($courses as &$c) {
            $c['summary'] = $results->statusSummary((int) $c['id']);
        }
        unset($c);
        $this->render('lecturer/courses', ['title' => 'My courses', 'courses' => $courses]);
    }

    public function scores(): void
    {
        $courseId = (int) ($_GET['course'] ?? $_POST['course_id'] ?? 0);
        $course = (new CourseRepository())->find($courseId);
        if (!$course || (int) $course['lecturer_id'] !== Auth::id()) {
            $this->flash('error', 'You can only open courses assigned to you.');
            $this->redirect('lecturer/courses');
        }
        if ($this->isPost()) {
            $this->verifyCsrf();
            $service = new ResultService();
            try {
                if (($_POST['action'] ?? '') === 'submit') {
                    $n = $service->submit(Auth::id(), $courseId);
                    $this->flash('success', "{$n} result(s) submitted to the admin for approval.");
                } else {
                    $n = $service->recordScores(Auth::id(), $courseId, (array) ($_POST['scores'] ?? []));
                    $this->flash('success', "{$n} score(s) saved as draft.");
                }
            } catch (ValidationException $e) {
                $this->flash('error', $e->getMessage());
            }
            $this->redirect('lecturer/scores&course=' . $courseId);
        }
        $this->render('lecturer/scores', [
            'title' => 'Scores · ' . $course['code'],
            'course' => $course,
            'rows' => (new EnrollmentRepository())->forCourse($courseId),
            'summary' => (new ResultRepository())->statusSummary($courseId),
            'maxCa' => config('max_ca'),
            'maxExam' => config('max_exam'),
        ]);
    }
}
