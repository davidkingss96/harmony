<?php

declare(strict_types=1);

namespace App\Domain\Session;

final class Session
{
    /**
     * @param list<SessionItem> $items
     */
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly int $tuningId,
        public readonly string $createdAt,
        public readonly string $updatedAt,
        public readonly array $items = [],
    ) {
    }
}
