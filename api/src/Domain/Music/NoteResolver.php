<?php

declare(strict_types=1);

namespace App\Domain\Music;

/**
 * Resolves a formula against a root note to absolute chromatic positions (0-11).
 */
final class NoteResolver
{
    public function resolve(IntervalFormula $formula, int $rootNote): array
    {
        if ($rootNote < 0 || $rootNote >= NoteNames::count()) {
            throw new \InvalidArgumentException(sprintf('Root note must be 0-%d, got %d', NoteNames::count() - 1, $rootNote));
        }

        return array_map(
            static fn (int $interval): int => ($rootNote + $interval) % NoteNames::count(),
            $formula->intervals()
        );
    }
}
