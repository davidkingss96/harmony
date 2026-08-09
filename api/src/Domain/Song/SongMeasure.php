<?php

declare(strict_types=1);

namespace App\Domain\Song;

final class SongMeasure
{
    /**
     * @param list<SongEvent> $events
     */
    public function __construct(
        public readonly int $id,
        public readonly int $sectionId,
        public readonly int $position,
        public readonly array $events = [],
    ) {
    }
}
