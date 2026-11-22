<?php
declare(strict_types=1);

/**
 * Creates the database tables and loads demo data.
 *   php database/install.php                 -> uses config/config.php (MySQL by default)
 *   DB_DRIVER=sqlite php database/install.php -> local SQLite file, no MySQL needed
 *   php database/install.php --export-mysql   -> also writes database/srms_full.sql (schema + data) for phpMyAdmin import
 */

require __DIR__ . '/../src/bootstrap.php';

use App\Core\Database;
use App\Models\CourseRepository;
use App\Models\EnrollmentRepository;
use App\Models\StudentRepository;
use App\Models\UserRepository;
use App\Services\ResultService;

function install(PDO $pdo, string $driver): void
{
    $schema = file_get_contents(__DIR__ . "/schema.{$driver}.sql");
    if ($driver === 'mysql') {
        // the database itself is created by the schema file
        $schema = preg_replace('/^(CREATE DATABASE|USE)\b.*$/mi', '', $schema);
    }
    $pdo->exec($schema);
}

function seed(): array
{
    $users = new UserRepository();
    $students = new StudentRepository();
    $courses = new CourseRepository();
    $enrol = new EnrollmentRepository();

    $admin = $users->create('admin', 'admin123', 'Registry Administrator', 'admin');
    $mensah = $users->create('k.mensah', 'lecturer123', 'Dr. Kwame Mensah', 'lecturer');
    $owusu = $users->create('a.owusu', 'lecturer123', 'Mrs. Abena Owusu', 'lecturer');

    $people = [
        ['UEB3201021', 'Ama Serwaa Boateng'], ['UEB3201022', 'Kofi Asante'], ['UEB3201023', 'Esi Nyarko'],
        ['UEB3201024', 'Yaw Darko'], ['UEB3201025', 'Akosua Frimpong'], ['UEB3201026', 'Kwabena Ofori'],
    ];
    $sids = [];
    foreach ($people as [$index, $name]) {
        $uid = $users->create($index, 'student123', $name, 'student');
        $sids[] = $students->create($uid, $index, 'BSc Computer Science', 200);
    }

    $c1 = $courses->create('CSC 201', 'Data Structures and Algorithms', 3, 1, '2025/2026', $mensah);
    $c2 = $courses->create('CSC 203', 'Database Systems', 3, 1, '2025/2026', $owusu);
    $c3 = $courses->create('MTH 201', 'Discrete Mathematics', 2, 1, '2025/2026', $owusu);
    $c4 = $courses->create('CSC 202', 'Software Engineering', 3, 2, '2025/2026', $mensah);
    $c5 = $courses->create('CSC 204', 'Web Development', 3, 2, '2025/2026', $owusu);

    $map = [];
    foreach ([$c1, $c2, $c3, $c4, $c5] as $cid) {
        foreach ($sids as $sid) {
            $map[$cid][] = $enrol->enroll($sid, $cid);
        }
    }

    $svc = new ResultService();
    // [ca, exam] per student, in the order of $people
    $scores = [
        $c1 => [[36, 52], [28, 41], [33, 47], [22, 30], [38, 55], [25, 36]],
        $c2 => [[34, 48], [30, 39], [35, 50], [20, 27], [37, 51], [27, 33]],
        $c3 => [[32, 45], [26, 35], [30, 44], [18, 25], [36, 50], [24, 31]],
        $c4 => [[35, 50], [29, 40], [34, 46], [21, 29], [39, 56], [26, 35]],
        $c5 => [[33, 49], [27, 38], [31, 42], [19, 28], [35, 53], null],
    ];
    $lecturerOf = [$c1 => $mensah, $c2 => $owusu, $c3 => $owusu, $c4 => $mensah, $c5 => $owusu];
    foreach ($scores as $cid => $rows) {
        $payload = [];
        foreach ($rows as $i => $row) {
            if ($row !== null) {
                $payload[$map[$cid][$i]] = ['ca' => $row[0], 'exam' => $row[1]];
            }
        }
        $svc->recordScores($lecturerOf[$cid], $cid, $payload);
    }
    // Semester 1 results: submitted and published. CSC 202: submitted, waiting for admin. CSC 204: draft, one score missing.
    foreach ([$c1, $c2, $c3] as $cid) {
        $svc->submit($lecturerOf[$cid], $cid);
        $svc->publish($admin, $cid);
    }
    $svc->submit($mensah, $c4);

    return ['admin' => $admin];
}

function exportMysql(PDO $pdo, string $file): void
{
    $out = file_get_contents(__DIR__ . '/schema.mysql.sql') . "\n-- Demo data\n";
    foreach (['users', 'students', 'courses', 'enrollments', 'results', 'audit_log'] as $table) {
        foreach ($pdo->query("SELECT * FROM {$table} ORDER BY id")->fetchAll() as $row) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v), array_values($row));
            $out .= "INSERT INTO {$table} (" . implode(', ', array_keys($row)) . ') VALUES (' . implode(', ', $vals) . ");\n";
        }
    }
    file_put_contents($file, $out);
}

$cfg = config('db');
if ($cfg['driver'] === 'mysql') {
    $server = new PDO("mysql:host={$cfg['host']};port={$cfg['port']};charset=utf8mb4", $cfg['username'], $cfg['password']);
    $server->exec("CREATE DATABASE IF NOT EXISTS `{$cfg['database']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
} elseif (is_file($cfg['sqlite_path'])) {
    unlink($cfg['sqlite_path']);
}

$pdo = Database::connection();
install($pdo, $cfg['driver']);
seed();
echo "Database ready ({$cfg['driver']}).\n";
echo "Sign in as admin / admin123, k.mensah / lecturer123, or UEB3201021 / student123.\n";

if (in_array('--export-mysql', $argv, true)) {
    exportMysql($pdo, __DIR__ . '/srms_full.sql');
    echo "Wrote database/srms_full.sql\n";
}
