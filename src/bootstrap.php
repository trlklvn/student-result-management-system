<?php
declare(strict_types=1);

// Ghana time (GMT, no daylight saving). Without this PHP uses php.ini's zone and audit times were 1 hour off (DEF-03).
date_default_timezone_set('Africa/Accra');

// PSR-4 style autoloader: App\Foo\Bar -> src/Foo/Bar.php
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    if (strncmp($class, $prefix, strlen($prefix)) !== 0) {
        return;
    }
    $file = __DIR__ . '/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_file($file)) {
        require $file;
    }
});

function config(?string $key = null)
{
    static $config = null;
    if ($config === null) {
        $config = require __DIR__ . '/../config/config.php';
    }
    return $key === null ? $config : ($config[$key] ?? null);
}

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
