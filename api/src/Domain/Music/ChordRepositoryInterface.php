<?php

declare(strict_types=1);

namespace App\Domain\Music;

interface ChordRepositoryInterface
{
    /** @return list<array{id: int, name: string, formula: string}> */
    public function findAllChords(): array;

    public function findChordFormula(int $id): ?string;
}
