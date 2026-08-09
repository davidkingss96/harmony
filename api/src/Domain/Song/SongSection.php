<?php

declare(strict_types=1);

namespace App\Domain\Song;

final class SongSection
{
    /**
     * @param list<SongMeasure> $measures
     */
    public function __construct(
        public readonly int $id,
        public readonly int $songId,
        public readonly string $name,
        public readonly string $color,
        public readonly int $position,
        public readonly array $measures = [],
    ) {
    }
}
