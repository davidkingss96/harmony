<?php

declare(strict_types=1);

namespace App\Domain\Song;

final class SongEvent
{
    public const TYPE_CHORD = 'CHORD';
    public const TYPE_SCALE = 'SCALE';

    public function __construct(
        public readonly int $id,
        public readonly int $measureId,
        public readonly int $beat,
        public readonly string $elementType,
        public readonly int $elementId,
        public readonly int $rootNote,
        public readonly ?string $notes,
        public readonly ?string $elementName = null,
        public readonly ?string $rootNoteName = null,
    ) {
    }
}
