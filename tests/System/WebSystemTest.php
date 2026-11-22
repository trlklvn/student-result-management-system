<?php
declare(strict_types=1);

/**
 * End-to-end tests through HTTP, like a real browser:
 * starts PHP's built-in web server on a fresh demo database and drives the pages.
 */
final class WebSystemTest extends TestCase
{
    private static $proc = null;
    private static string $base = 'http://127.0.0.1:8099/index.php';
    private array $cookies = [];

    public static function boot(): void
    {
        if (self::$proc) {
            return;
        }
        $root = realpath(__DIR__ . '/../..');
        $dbFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'srms_system_test.sqlite';
        $env = array_merge(getenv(), ['DB_DRIVER' => 'sqlite', 'DB_SQLITE_PATH' => $dbFile]);
        $php = PHP_BINARY;
        $install = proc_open([$php, "$root/database/install.php"], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, $root, $env);
        stream_get_contents($pipes[1]);
        proc_close($install);
        self::$proc = proc_open([$php, '-S', '127.0.0.1:8099', '-t', "$root/public"], [1 => ['file', 'NUL', 'w'], 2 => ['file', 'NUL', 'w']], $p, $root, $env);
        for ($i = 0; $i < 50; $i++) {
            if (@fsockopen('127.0.0.1', 8099)) {
                return;
            }
            usleep(100000);
        }
        throw new RuntimeException('Test web server did not start');
    }

    public static function shutdown(): void
    {
        if (self::$proc) {
            proc_terminate(self::$proc);
            self::$proc = null;
        }
    }

    public function setUp(): void
    {
        self::boot();
        $this->cookies = [];
    }

    /** @return array{status:int, body:string, location:?string} */
    private function request(string $route, array $post = null): array
    {
        $headers = [];
        if ($this->cookies) {
            $headers[] = 'Cookie: ' . implode('; ', array_map(fn($k, $v) => "$k=$v", array_keys($this->cookies), $this->cookies));
        }
        $opts = ['http' => ['method' => $post === null ? 'GET' : 'POST', 'ignore_errors' => true, 'follow_location' => 0]];
        if ($post !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
            $opts['http']['content'] = http_build_query($post);
        }
        $opts['http']['header'] = implode("\r\n", $headers);
        $body = file_get_contents(self::$base . '?r=' . $route, false, stream_context_create($opts));
        $status = 0;
        $location = null;
        foreach ($http_response_header as $h) {
            if (preg_match('#^HTTP/\S+ (\d+)#', $h, $m)) $status = (int) $m[1];
            if (preg_match('/^Set-Cookie: ([^=]+)=([^;]*)/i', $h, $m)) $this->cookies[$m[1]] = $m[2];
            if (preg_match('/^Location: (.*)$/i', $h, $m)) $location = trim($m[1]);
        }
        return ['status' => $status, 'body' => (string) $body, 'location' => $location];
    }

    private function csrf(string $route): string
    {
        preg_match('/name="csrf" value="([a-f0-9]+)"/', $this->request($route)['body'], $m);
        return $m[1] ?? '';
    }

    private function login(string $user, string $pass): array
    {
        $token = $this->csrf('auth/login');
        return $this->request('auth/login', ['csrf' => $token, 'username' => $user, 'password' => $pass]);
    }

    /** ST-01 Valid admin login lands on the dashboard */
    public function testAdminLogin(): void
    {
        $r = $this->login('admin', 'admin123');
        $this->assertSame(302, $r['status']);
        $this->assertContains('admin/dashboard', (string) $r['location']);
        $this->assertContains('Awaiting approval', $this->request('admin/dashboard')['body']);
    }

    /** ST-02 Wrong password shows an error and does not sign in */
    public function testWrongPassword(): void
    {
        $r = $this->login('admin', 'wrong');
        $this->assertSame(200, $r['status']);
        $this->assertContains('Wrong username or password', $r['body']);
    }

    /** ST-03 Pages are protected: guests are redirected, students cannot open admin pages */
    public function testAccessControl(): void
    {
        $this->assertSame(302, $this->request('admin/dashboard')['status']);
        $this->login('UEB3201021', 'student123');
        $this->assertSame(403, $this->request('admin/students')['status']);
    }

    /** ST-04 A form posted without the CSRF token is rejected */
    public function testCsrfRequired(): void
    {
        $r = $this->request('auth/login', ['username' => 'admin', 'password' => 'admin123']);
        $this->assertSame(419, $r['status']);
    }

    /** ST-05 Student sees published semester-1 results and CGPA, but not unpublished semester-2 results */
    public function testStudentTranscript(): void
    {
        $this->login('UEB3201021', 'student123');
        $body = $this->request('student/results')['body'];
        $this->assertContains('CSC 201', $body);
        $this->assertContains('First Class', $body);
        $this->assertTrue(!str_contains($body, 'CSC 202'), 'unpublished CSC 202 must be hidden');
    }

    /** ST-06 End to end: admin publishes CSC 202, then the student can see it */
    public function testPublishThenStudentSees(): void
    {
        $this->login('admin', 'admin123');
        $dash = $this->request('admin/dashboard')['body'];
        preg_match('#CSC 202.*?admin/review&amp;course=(\d+)#s', $dash, $m);
        $courseId = $m[1] ?? '4';
        $token = $this->csrf("admin/review&course=$courseId");
        $r = $this->request("admin/review&course=$courseId", ['csrf' => $token, 'action' => 'publish']);
        $this->assertSame(302, $r['status']);
        $this->assertContains('published', $this->request("admin/review&course=$courseId")['body']);
        $this->request('auth/logout');
        $this->cookies = [];
        $this->login('UEB3201021', 'student123');
        $this->assertContains('CSC 202', $this->request('student/results')['body']);
    }

    /** ST-07 Lecturer entering an out-of-range score gets a validation message and nothing is saved */
    public function testLecturerValidation(): void
    {
        $this->login('a.owusu', 'lecturer123');
        $courses = $this->request('lecturer/courses')['body'];
        preg_match('#lecturer/scores&amp;course=(\d+)">\s*<strong>CSC 204#', $courses, $m);
        $courseId = $m[1] ?? '5';
        $page = $this->request("lecturer/scores&course=$courseId")['body'];
        preg_match('/name="csrf" value="([a-f0-9]+)"/', $page, $t);
        preg_match_all('/scores\[(\d+)\]\[ca\]/', $page, $ids);
        $last = end($ids[1]);
        $this->request("lecturer/scores&course=$courseId", ['csrf' => $t[1], 'action' => 'save', 'scores' => [$last => ['ca' => 45, 'exam' => 50]]]);
        $after = $this->request("lecturer/scores&course=$courseId")['body'];
        $this->assertContains('CA score must be between 0 and 40', $after);
    }

    /** ST-09 Typing the last missing score and pressing Submit saves it and submits the course (regression test for DEF-02) */
    public function testSubmitSavesTypedScores(): void
    {
        $this->login('a.owusu', 'lecturer123');
        $courseId = '5';
        $page = $this->request("lecturer/scores&course=$courseId")['body'];
        preg_match('/name="csrf" value="([a-f0-9]+)"/', $page, $t);
        preg_match_all('/scores\[(\d+)\]\[ca\]/', $page, $ids);
        $last = end($ids[1]);
        $this->request("lecturer/scores&course=$courseId", ['csrf' => $t[1], 'action' => 'submit', 'scores' => [$last => ['ca' => 30, 'exam' => 40]]]);
        $after = $this->request("lecturer/scores&course=$courseId")['body'];
        $this->assertContains('submitted to the admin for approval', $after);
        $this->assertTrue(!str_contains($after, 'no score'), 'no student should be left without a score');
    }

    /** ST-08 Admin registers a new student who can then sign in */
    public function testRegisterStudent(): void
    {
        $this->login('admin', 'admin123');
        $token = $this->csrf('admin/students');
        $this->request('admin/students', ['csrf' => $token, 'index_number' => 'UEB3209999', 'full_name' => 'Test Student',
            'programme' => 'BSc IT', 'level' => '100', 'password' => 'secret12']);
        $this->assertContains('UEB3209999', $this->request('admin/students')['body']);
        $this->request('auth/logout');
        $this->cookies = [];
        $r = $this->login('UEB3209999', 'secret12');
        $this->assertContains('student/results', (string) $r['location']);
    }
}
