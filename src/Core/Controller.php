<?php
declare(strict_types=1);

namespace App\Core;

/** Base controller: rendering, redirects, flash messages and CSRF protection. */
abstract class Controller
{
    protected function render(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $flash = $_SESSION['flash'] ?? null;
        unset($_SESSION['flash']);
        $user = Auth::user();
        $csrf = self::csrfToken();
        ob_start();
        require __DIR__ . '/../../views/' . $view . '.php';
        $content = ob_get_clean();
        require __DIR__ . '/../../views/layout.php';
    }

    protected function redirect(string $route): void
    {
        header('Location: index.php?r=' . $route);
        exit;
    }

    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = ['type' => $type, 'message' => $message];
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf'])) {
            $_SESSION['csrf'] = bin2hex(random_bytes(16));
        }
        return $_SESSION['csrf'];
    }

    protected function verifyCsrf(): void
    {
        $token = $_POST['csrf'] ?? '';
        if (!hash_equals(self::csrfToken(), (string) $token)) {
            http_response_code(419);
            exit('Your session expired. Go back, refresh the page and try again.');
        }
    }

    protected function isPost(): bool
    {
        return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
    }
}
