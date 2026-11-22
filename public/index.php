<?php
declare(strict_types=1);

// Front controller: the single entry point for every page.
require __DIR__ . '/../src/bootstrap.php';

use App\Controllers\AdminController;
use App\Controllers\AuthController;
use App\Controllers\LecturerController;
use App\Controllers\StudentController;
use App\Core\Router;

session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
session_start();

$router = new Router();
$router->add('auth/login', AuthController::class, 'login');
$router->add('auth/logout', AuthController::class, 'logout');

$router->add('admin/dashboard', AdminController::class, 'dashboard', 'admin');
$router->add('admin/students', AdminController::class, 'students', 'admin');
$router->add('admin/courses', AdminController::class, 'courses', 'admin');
$router->add('admin/review', AdminController::class, 'review', 'admin');

$router->add('lecturer/courses', LecturerController::class, 'courses', 'lecturer');
$router->add('lecturer/scores', LecturerController::class, 'scores', 'lecturer');

$router->add('student/results', StudentController::class, 'results', 'student');

try {
    $router->dispatch((string) ($_GET['r'] ?? 'auth/login'));
} catch (PDOException $e) {
    http_response_code(500);
    error_log($e->getMessage());
    echo 'The database is not available. Check config/config.php and that MySQL is running.';
}
