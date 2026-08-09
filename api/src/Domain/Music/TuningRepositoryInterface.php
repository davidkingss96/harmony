<?php

declare(strict_types=1);

namespace App\Domain\Music;

interface TuningRepositoryInterface
{
    /** @return list<array{id: int, name: string, notes: string}> */
    public function findAllTunings(): array;

    public function findById(int $id): ?Tuning;
}
