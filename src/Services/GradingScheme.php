<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Strategy pattern: the grading rules are behind an interface,
 * so a faculty with a different scale can plug in its own scheme
 * without changing ResultService.
 */
interface GradingScheme
{
    /** @return array{grade:string, point:float} */
    public function grade(float $total): array;

    public function classify(float $cgpa): string;
}
