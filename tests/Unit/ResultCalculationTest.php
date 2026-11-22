<?php
declare(strict_types=1);

use App\Services\ResultService;
use App\Services\ValidationException;

final class ResultCalculationTest extends TestCase
{
    private ResultService $svc;

    public function setUp(): void
    {
        $this->freshDatabase(); // repositories need a connection even though compute() never queries
        $this->svc = new ResultService();
    }

    /** UT-05 Total = CA + exam, and the grade comes from the total */
    public function testComputeTotalAndGrade(): void
    {
        $this->assertSame(['total' => 88.0, 'grade' => 'A', 'point' => 4.0], $this->svc->compute(36, 52));
        $this->assertSame(['total' => 69.0, 'grade' => 'C+', 'point' => 2.5], $this->svc->compute(28, 41));
    }

    /** UT-06 CA above 40 or exam above 60 is rejected with a clear message */
    public function testScoreLimits(): void
    {
        $this->assertThrows(ValidationException::class, fn() => $this->svc->compute(41, 50), 'CA score must be between 0 and 40');
        $this->assertThrows(ValidationException::class, fn() => $this->svc->compute(30, 61), 'Exam score must be between 0 and 60');
        $this->assertThrows(ValidationException::class, fn() => $this->svc->compute(-1, 50));
    }

    /** UT-07 GPA is weighted by credit hours */
    public function testWeightedGpa(): void
    {
        // (4.0*3 + 2.0*2) / 5 = 3.2
        [$gpa, $points, $credits] = ResultService::gpa([
            ['grade_point' => 4.0, 'credit_hours' => 3],
            ['grade_point' => 2.0, 'credit_hours' => 2],
        ]);
        $this->assertSame(3.2, $gpa);
        $this->assertSame(16.0, $points);
        $this->assertSame(5, $credits);
    }

    /** UT-08 GPA of no courses is 0 (no division by zero) */
    public function testGpaEmpty(): void
    {
        $this->assertSame([0.0, 0.0, 0], ResultService::gpa([]));
    }
}
