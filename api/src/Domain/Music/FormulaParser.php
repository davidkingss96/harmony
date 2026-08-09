<?php

declare(strict_types=1);

namespace App\Domain\Music;

/**
 * Parses a comma-separated interval formula string into an IntervalFormula.
 */
final class FormulaParser
{
    public function parse(string $formula): IntervalFormula
    {
        $parts = array_map('trim', explode(',', $formula));

        $intervals = [];
        foreach ($parts as $part) {
            if (!is_numeric($part)) {
                throw new \InvalidArgumentException(sprintf('Invalid interval "%s"', $part));
            }

            $intervals[] = (int) $part;
        }

        return new IntervalFormula($intervals);
    }
}
