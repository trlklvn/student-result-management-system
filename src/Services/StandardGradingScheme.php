<?php
declare(strict_types=1);

namespace App\Services;

/** Default 4.0 scale used by many Ghanaian universities. */
final class StandardGradingScheme implements GradingScheme
{
    /** lower bound => [grade, grade point], highest first */
    private const BANDS = [
        80 => ['A', 4.0],
        75 => ['B+', 3.5],
        70 => ['B', 3.0],
        65 => ['C+', 2.5],
        60 => ['C', 2.0],
        55 => ['D+', 1.5],
        50 => ['D', 1.0],
        0  => ['F', 0.0],
    ];

    public function grade(float $total): array
    {
        if ($total < 0 || $total > 100) {
            throw new ValidationException('Total score must be between 0 and 100.');
        }
        foreach (self::BANDS as $min => [$grade, $point]) {
            if ($total >= $min) {
                return ['grade' => $grade, 'point' => $point];
            }
        }
        return ['grade' => 'F', 'point' => 0.0];
    }

    public function classify(float $cgpa): string
    {
        return match (true) {
            $cgpa >= 3.6 => 'First Class',
            $cgpa >= 3.0 => 'Second Class Upper',
            $cgpa >= 2.5 => 'Second Class Lower',
            $cgpa >= 2.0 => 'Third Class',
            $cgpa >= 1.0 => 'Pass',
            default      => 'Fail',
        };
    }

    public static function bands(): array
    {
        return self::BANDS;
    }
}
