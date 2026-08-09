<?php

declare(strict_types=1);

namespace App\Domain\Session;

final class SessionItem
{
    public const TYPE_CHORD = 'CHORD';
    public const TYPE_SCALE = 'SCALE';

    public function __construct(
        public readonly int $id,
        public readonly int $sessionId,
        public readonly string $type,
        public readonly int $referenceId,
        public readonly int $rootNote,
        public readonly int $position,
    ) {
        if (!in_array($type, [self::TYPE_CHORD, self::TYPE_SCALE], true)) {
            throw new \InvalidArgumentException(sprintf('Unknown session item type "%s"', $type));
        }
    }
}
