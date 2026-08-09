<?php

declare(strict_types=1);

namespace App\Domain\Song;

final class Song
{
    /**
     * @param list<SongSection> $sections
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly float $bpm,
        public readonly int $tuningId,
        public readonly int $timeSignatureNum,
        public readonly int $timeSignatureDen,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly array $sections = [],
    ) {
    }
}
