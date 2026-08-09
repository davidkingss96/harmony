<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Music\ChordRepositoryInterface;
use App\Domain\Music\FormulaParser;
use App\Domain\Music\HeatmapCalculator;
use App\Domain\Music\IntervalFormula;
use App\Domain\Music\NoteNames;
use App\Domain\Music\NoteResolver;
use App\Domain\Music\ScaleRepositoryInterface;
use App\Domain\Music\TuningRepositoryInterface;

/**
 * Orchestrates heatmap calculation for a tuning and a set of items
 * (chords/scales). Depends only on abstractions.
 */
final class HeatmapService
{
    public function __construct(
        private readonly TuningRepositoryInterface $tunings,
        private readonly ChordRepositoryInterface $chords,
        private readonly ScaleRepositoryInterface $scales,
        private readonly FormulaParser $parser,
        private readonly NoteResolver $resolver,
        private readonly HeatmapCalculator $calculator,
    ) {
    }

    /**
     * @param list<array{type: string, reference_id: int, root_note: int}> $items
     *
     * @return array{tuning: array{id: int, name: string, notes: string}, influence: list<int>, heatmap: list<array<string, int|string>>}
     */
    public function calculate(int $tuningId, array $items): array
    {
        $tuning = $this->tunings->findById($tuningId);
        if ($tuning === null) {
            throw new NotFoundException('Tuning not found');
        }

        if ($items === []) {
            throw new ValidationException(['At least one item is required']);
        }

        $influence = array_fill(0, NoteNames::count(), 0);

        foreach ($items as $item) {
            $formula = $this->resolveFormula($item);
            foreach ($this->resolver->resolve($formula, $this->requireRootNote($item)) as $note) {
                $influence[$note]++;
            }
        }

        return [
            'tuning' => [
                'id' => $tuning->id,
                'name' => $tuning->name,
                'notes' => implode(',', $tuning->notePositions),
            ],
            'influence' => $influence,
            'heatmap' => $this->calculator->calculate($tuning, $influence)->positions,
        ];
    }

    /**
     * @param array<string, mixed> $item
     */
    private function resolveFormula(array $item): IntervalFormula
    {
        $type = $item['type'] ?? null;
        $referenceId = (int) ($item['reference_id'] ?? 0);

        $formula = $type === 'CHORD'
            ? $this->chords->findChordFormula($referenceId)
            : $this->scales->findScaleFormula($referenceId);

        if ($formula === null) {
            throw new ValidationException([sprintf('Unknown %s id "%d"', $type ?? 'item', $referenceId)]);
        }

        return $this->parser->parse($formula);
    }

    /**
     * @param array<string, mixed> $item
     */
    private function requireRootNote(array $item): int
    {
        $root = $item['root_note'] ?? null;
        if (!is_numeric($root) || (int) $root < 0 || (int) $root >= NoteNames::count()) {
            throw new ValidationException([sprintf('Invalid root_note "%s"', (string) $root)]);
        }

        return (int) $root;
    }
}
