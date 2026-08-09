<?php

declare(strict_types=1);

namespace App\Domain\Music;

/**
 * Pure algorithm that maps per-note influence counts (0-11)
 * onto every string/fret position of a tuning.
 */
final class HeatmapCalculator
{
    public const NUM_FRETS = 13;

    /**
     * @param list<int> $influence 12 counts (chromatic position => occurrences)
     */
    public function calculate(Tuning $tuning, array $influence): Heatmap
    {
        $maxInfluence = max($influence);

        $positions = [];
        foreach ($tuning->notePositions as $stringIndex => $openNote) {
            for ($fret = 0; $fret < self::NUM_FRETS; $fret++) {
                $notePosition = ($openNote + $fret) % NoteNames::count();
                $influenceValue = $influence[$notePosition];
                $percentage = $maxInfluence > 0
                    ? (int) round(($influenceValue / $maxInfluence) * 100)
                    : 0;

                $positions[] = [
                    'string' => $stringIndex + 1,
                    'fret' => $fret,
                    'note' => NoteNames::name($notePosition),
                    'note_position' => $notePosition,
                    'influence' => $influenceValue,
                    'percentage' => $percentage,
                ];
            }
        }

        return new Heatmap($positions);
    }
}
