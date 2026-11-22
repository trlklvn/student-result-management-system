<?php
declare(strict_types=1);

use App\Services\StandardGradingScheme;
use App\Services\ValidationException;

final class GradingSchemeTest extends TestCase
{
    private StandardGradingScheme $s;

    public function setUp(): void
    {
        $this->s = new StandardGradingScheme();
    }

    /** UT-01 Every grade band returns the right letter and point at its lower boundary */
    public function testBandLowerBoundaries(): void
    {
        $expected = [80 => ['A', 4.0], 75 => ['B+', 3.5], 70 => ['B', 3.0], 65 => ['C+', 2.5],
                     60 => ['C', 2.0], 55 => ['D+', 1.5], 50 => ['D', 1.0], 0 => ['F', 0.0]];
        foreach ($expected as $score => [$grade, $point]) {
            $this->assertSame(['grade' => $grade, 'point' => $point], $this->s->grade((float) $score), "score $score");
        }
    }

    /** UT-02 Scores just below a boundary fall into the lower band */
    public function testJustBelowBoundary(): void
    {
        $this->assertSame('B+', $this->s->grade(79.5)['grade']);
        $this->assertSame('F', $this->s->grade(49.99)['grade']);
        $this->assertSame('A', $this->s->grade(100.0)['grade']);
    }

    /** UT-03 Totals outside 0–100 are rejected */
    public function testOutOfRangeRejected(): void
    {
        $this->assertThrows(ValidationException::class, fn() => $this->s->grade(100.5));
        $this->assertThrows(ValidationException::class, fn() => $this->s->grade(-1));
    }

    /** UT-04 CGPA maps to the correct class of degree */
    public function testClassification(): void
    {
        $this->assertSame('First Class', $this->s->classify(3.6));
        $this->assertSame('Second Class Upper', $this->s->classify(3.59));
        $this->assertSame('Second Class Lower', $this->s->classify(2.5));
        $this->assertSame('Third Class', $this->s->classify(2.0));
        $this->assertSame('Pass', $this->s->classify(1.0));
        $this->assertSame('Fail', $this->s->classify(0.99));
    }
}
