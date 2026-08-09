<?php

declare(strict_types=1);

namespace App\Domain\Music;

/**
 * Immutable result of a heatmap calculation.
 *
 * @param list<array<string, int|string>> $positions per-position cells
 *        with keys: string, fret, note, note_position, influence, percentage
 */
final class Heatmap
{
    /** @param list<array<string, int|string>> $positions */
    public function __construct(public readonly array $positions)
    {
    }
}
