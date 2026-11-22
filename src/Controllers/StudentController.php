<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\StudentRepository;
use App\Services\ResultService;

final class StudentController extends Controller
{
    public function results(): void
    {
        $student = (new StudentRepository())->findByUserId(Auth::id());
        if (!$student) {
            http_response_code(404);
            exit('No student record is linked to this account. Contact the administrator.');
        }
        $this->render('student/results', [
            'title' => 'My results',
            'student' => $student,
            'transcript' => (new ResultService())->transcript((int) $student['id']),
        ]);
    }
}
