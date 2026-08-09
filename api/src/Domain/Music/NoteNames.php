<?php

declare(strict_types=1);

namespace App\Domain\Music;

/**
 * Single source of truth for chromatic note names (positions 0-11).
 */
final class NoteNames
{
    private const NAMES = ['C', 'C#', 'D', 'D#', 'E', 'F', 'F#', 'G', 'G#', 'A', 'A#', 'B'];

    public static function count(): int
    {
        return count(self::NAMES);
    }

    public static function name(int $position): string
    {
        if ($position < 0 || $position >= self::count()) {
            throw new \InvalidArgumentException(sprintf('Note position must be 0-%d, got %d', self::count() - 1, $position));
        }

        return self::NAMES[$position];
    }

    /** @return list<string> */
    public static function all(): array
    {
        return self::NAMES;
    }
}
