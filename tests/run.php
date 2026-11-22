<?php
declare(strict_types=1);

/**
 * Test runner:  php tests/run.php [unit|integration|system]
 * Prints each test with its ID and writes tests/results.json for the test report.
 */
require __DIR__ . '/../src/bootstrap.php';
require __DIR__ . '/TestCase.php';

ini_set('assert.exception', '1');
$only = $argv[1] ?? null;
$suites = ['Unit' => 'unit', 'Integration' => 'integration', 'System' => 'system'];
$results = [];
$failed = 0;

foreach ($suites as $dir => $name) {
    if ($only && $only !== $name) {
        continue;
    }
    echo "\n== " . strtoupper($name) . " TESTS ==\n";
    foreach (glob(__DIR__ . "/$dir/*Test.php") as $file) {
        require_once $file;
        $class = basename($file, '.php');
        $ref = new ReflectionClass($class);
        foreach ($ref->getMethods(ReflectionMethod::IS_PUBLIC) as $m) {
            if (!str_starts_with($m->name, 'test')) {
                continue;
            }
            preg_match('/\b([A-Z]{2}-\d{2})\s+(.+)/', (string) $m->getDocComment(), $doc);
            $id = $doc[1] ?? '';
            $desc = trim(str_replace('*/', '', $doc[2] ?? $m->name));
            $t = new $class();
            $start = microtime(true);
            try {
                $t->setUp();
                $t->{$m->name}();
                $status = 'PASS';
                $error = '';
            } catch (Throwable $e) {
                $status = 'FAIL';
                $error = $e->getMessage();
                $failed++;
            } finally {
                $t->tearDown();
            }
            $ms = round((microtime(true) - $start) * 1000);
            printf("  [%s] %-6s %s (%d ms)%s\n", $status, $id, $desc, $ms, $error ? "\n         -> $error" : '');
            $results[] = compact('id', 'name', 'class', 'desc', 'status', 'error', 'ms');
        }
    }
}
if (class_exists('WebSystemTest', false)) {
    WebSystemTest::shutdown();
}

$total = count($results);
echo "\n$total tests, " . ($total - $failed) . " passed, $failed failed\n";
file_put_contents(__DIR__ . '/results.json', json_encode(['run_at' => date('Y-m-d H:i:s'), 'php' => PHP_VERSION, 'results' => $results], JSON_PRETTY_PRINT));
exit($failed ? 1 : 0);
