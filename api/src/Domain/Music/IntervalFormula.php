<?php

declare(strict_types=1);

namespace App\Domain\Music;

/**
 * Immutable value object representing a parsed interval formula
 * (e.g. "0,4,7" -> [0, 4, 7]).
 */
final class IntervalFormula
{
    /** @param list<int> $intervals */
    public function __construct(private readonly array $intervals)
    {
        if ($intervals === []) {
            throw new \InvalidArgumentException('Interval formula cannot be empty');
        }
    }

    /** @return list<int> */
    public function intervals(): array
    {
        return $this->intervals;
    }
}
