<?php

declare(strict_types=1);

namespace App\Domain\Music;

interface NoteRepositoryInterface
{
    /** @return list<array{id: int, name: string, chromatic_position: int}> */
    public function findAllNotes(): array;
}
