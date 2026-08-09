<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Music\ChordRepositoryInterface;
use App\Domain\Music\NoteRepositoryInterface;
use App\Domain\Music\ScaleRepositoryInterface;
use App\Domain\Music\Tuning;
use App\Domain\Music\TuningRepositoryInterface;
use App\Infrastructure\Database\MySqlRepository;

/**
 * Read-only catalog data (notes, chords, scales, tunings).
 */
final class MySqlCatalogRepository extends MySqlRepository implements
    NoteRepositoryInterface,
    ChordRepositoryInterface,
    ScaleRepositoryInterface,
    TuningRepositoryInterface
{
    public function findAllNotes(): array
    {
        return $this->db->query('SELECT id, name, chromatic_position FROM notes ORDER BY chromatic_position')->fetchAll();
    }

    public function findAllChords(): array
    {
        return $this->db->query('SELECT id, name, formula FROM chords ORDER BY name')->fetchAll();
    }

    public function findAllScales(): array
    {
        return $this->db->query('SELECT id, name, formula FROM scales ORDER BY name')->fetchAll();
    }

    public function findAllTunings(): array
    {
        return $this->db->query('SELECT id, name, notes FROM tunings ORDER BY name')->fetchAll();
    }

    public function findById(int $id): ?Tuning
    {
        $stmt = $this->db->prepare('SELECT id, name, notes FROM tunings WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new Tuning((int) $row['id'], $row['name'], array_map('intval', explode(',', $row['notes'])));
    }

    public function findChordFormula(int $id): ?string
    {
        return $this->findFormula('chords', $id);
    }

    public function findScaleFormula(int $id): ?string
    {
        return $this->findFormula('scales', $id);
    }

    private function findFormula(string $table, int $id): ?string
    {
        $stmt = $this->db->prepare(sprintf('SELECT formula FROM %s WHERE id = ?', $table));
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row['formula'];
    }
}
