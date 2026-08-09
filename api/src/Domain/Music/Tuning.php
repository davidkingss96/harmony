<?php

declare(strict_types=1);

namespace App\Domain\Music;

/**
 * Immutable value object describing a guitar tuning.
 *
 * @param list<int> $notePositions chromatic positions of the open strings
 *                                 (lowest string first), e.g. [4, 9, 2, 7, 11, 4]
 */
final class Tuning
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly array $notePositions,
    ) {
    }
}
