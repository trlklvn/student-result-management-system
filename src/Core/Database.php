<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Singleton pattern: one shared PDO connection per request.
 * setConnection() lets tests inject an isolated database.
 */
final class Database
{
    private static ?PDO $pdo = null;

    private function __construct() {}

    public static function connection(): PDO
    {
        if (self::$pdo === null) {
            self::$pdo = self::create(config('db'));
        }
        return self::$pdo;
    }

    public static function setConnection(?PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function create(array $db): PDO
    {
        if ($db['driver'] === 'sqlite') {
            $pdo = new PDO('sqlite:' . $db['sqlite_path']);
            $pdo->exec('PRAGMA foreign_keys = ON');
        } else {
            $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $db['host'], $db['port'], $db['database']);
            $pdo = new PDO($dsn, $db['username'], $db['password']);
        }
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    }

    public static function driver(): string
    {
        return self::connection()->getAttribute(PDO::ATTR_DRIVER_NAME);
    }
}
