<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Models\AuditRepository;

final class AuthController extends Controller
{
    private const HOME = ['admin' => 'admin/dashboard', 'lecturer' => 'lecturer/courses', 'student' => 'student/results'];

    public function login(): void
    {
        if ($user = Auth::user()) {
            $this->redirect(self::HOME[$user['role']]);
        }
        $error = null;
        $username = '';
        if ($this->isPost()) {
            $this->verifyCsrf();
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if (Auth::attempt($username, $password)) {
                (new AuditRepository())->log(Auth::id(), 'login', "{$username} signed in");
                $this->redirect(self::HOME[Auth::user()['role']]);
            }
            $error = 'Wrong username or password.';
        }
        $this->render('auth/login', ['error' => $error, 'username' => $username, 'title' => 'Sign in']);
    }

    public function logout(): void
    {
        Auth::logout();
        session_start();
        $this->flash('success', 'You have signed out.');
        $this->redirect('auth/login');
    }
}
