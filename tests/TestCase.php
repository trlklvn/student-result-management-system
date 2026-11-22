<?php
declare(strict_types=1);

/** Tiny xUnit-style test harness (no Composer needed). Each test method is public and starts with "test". */
abstract class TestCase
{
    public function setUp(): void {}
    public function tearDown(): void {}

    protected function assertSame($expected, $actual, string $msg = ''): void
    {
        if ($expected !== $actual) {
            throw new AssertionError(($msg ? "$msg: " : '') . 'expected ' . var_export($expected, true) . ', got ' . var_export($actual, true));
        }
    }

    protected function assertTrue(bool $cond, string $msg = 'condition is false'): void
    {
        if (!$cond) {
            throw new AssertionError($msg);
        }
    }

    protected function assertContains(string $needle, string $haystack, string $msg = ''): void
    {
        if (!str_contains($haystack, $needle)) {
            throw new AssertionError(($msg ? "$msg: " : '') . "text does not contain \"$needle\"");
        }
    }

    protected function assertThrows(string $class, callable $fn, ?string $messagePart = null): void
    {
        try {
            $fn();
        } catch (Throwable $e) {
            if (!$e instanceof $class) {
                throw new AssertionError("expected $class, got " . get_class($e) . ': ' . $e->getMessage());
            }
            if ($messagePart !== null && !str_contains($e->getMessage(), $messagePart)) {
                throw new AssertionError("exception message \"{$e->getMessage()}\" does not contain \"$messagePart\"");
            }
            return;
        }
        throw new AssertionError("expected $class to be thrown");
    }

    /** Fresh SQLite database with the real schema, shared through the Database singleton. */
    protected function freshDatabase(): PDO
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec(file_get_contents(__DIR__ . '/../database/schema.sqlite.sql'));
        \App\Core\Database::setConnection($pdo);
        return $pdo;
    }
}
