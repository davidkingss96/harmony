<?php

declare(strict_types=1);

namespace App\Domain\Music;

interface ScaleRepositoryInterface
{
    /** @return list<array{id: int, name: string, formula: string}> */
    public function findAllScales(): array;

    public function findScaleFormula(int $id): ?string;
}
