<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Front Controller + Router: every request enters through public/index.php
 * and is dispatched here to a controller action, after a role check.
 */
final class Router
{
    /** @var array<string, array{0:class-string,1:string,2:?string}> route => [controller, method, required role] */
    private array $routes = [];

    public function add(string $route, string $controller, string $method, ?string $role = null): void
    {
        $this->routes[$route] = [$controller, $method, $role];
    }

    public function dispatch(string $route): void
    {
        if (!isset($this->routes[$route])) {
            http_response_code(404);
            echo 'Page not found.';
            return;
        }
        [$controller, $method, $role] = $this->routes[$route];

        if ($role !== null) {
            if (Auth::user() === null) {
                header('Location: index.php?r=auth/login');
                exit;
            }
            if (!Auth::hasRole($role)) {
                http_response_code(403);
                echo 'You do not have permission to open this page.';
                return;
            }
        }
        (new $controller())->$method();
    }
}
