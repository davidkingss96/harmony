<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Song\Song;
use App\Domain\Song\SongEvent;
use App\Domain\Song\SongMeasure;
use App\Domain\Song\SongRepositoryInterface;
use App\Domain\Song\SongSection;
use App\Infrastructure\Database\MySqlRepository;

final class MySqlSongRepository extends MySqlRepository implements SongRepositoryInterface
{
    public function findAll(): array
    {
        return $this->db
            ->query('SELECT id, name, bpm, created_at FROM songs ORDER BY created_at DESC')
            ->fetchAll();
    }

    public function findById(int $id): ?Song
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM songs WHERE id = ?'
        );
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        if ($row === false) {
            return null;
        }

        return new Song(
            id: (int) $row['id'],
            name: $row['name'],
            bpm: (float) $row['bpm'],
            tuningId: (int) $row['tuning_id'],
            timeSignatureNum: (int) $row['time_signature_num'],
            timeSignatureDen: (int) $row['time_signature_den'],
            createdAt: $row['created_at'],
            updatedAt: $row['updated_at'],
            sections: $this->findSections((int) $row['id']),
        );
    }

    /** @return list<SongSection> */
    private function findSections(int $songId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM song_sections WHERE song_id = ? ORDER BY position'
        );
        $stmt->execute([$songId]);

        $sections = [];
        foreach ($stmt->fetchAll() as $row) {
            $sections[] = new SongSection(
                id: (int) $row['id'],
                songId: (int) $row['song_id'],
                name: $row['name'],
                color: $row['color'],
                position: (int) $row['position'],
                measures: $this->findMeasures((int) $row['id']),
            );
        }

        return $sections;
    }

    /** @return list<SongMeasure> */
    private function findMeasures(int $sectionId): array
    {
        $stmt = $this->db->prepare(
            'SELECT * FROM song_measures WHERE section_id = ? ORDER BY position'
        );
        $stmt->execute([$sectionId]);

        $measures = [];
        foreach ($stmt->fetchAll() as $row) {
            $measures[] = new SongMeasure(
                id: (int) $row['id'],
                sectionId: (int) $row['section_id'],
                position: (int) $row['position'],
                events: $this->findEvents((int) $row['id']),
            );
        }

        return $measures;
    }

    /** @return list<SongEvent> */
    public function findEvents(int $measureId): array
    {
        $sql = <<<'SQL'
            SELECT se.*,
                   CASE
                       WHEN se.element_type = 'CHORD' THEN c.name
                       WHEN se.element_type = 'SCALE' THEN s.name
                   END AS element_name,
                   n.name AS root_note_name
            FROM song_events se
            LEFT JOIN chords c ON se.element_type = 'CHORD' AND se.element_id = c.id
            LEFT JOIN scales s ON se.element_type = 'SCALE' AND se.element_id = s.id
            LEFT JOIN notes n ON se.root_note = n.chromatic_position
            WHERE se.measure_id = ?
            ORDER BY se.beat
        SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$measureId]);

        $events = [];
        foreach ($stmt->fetchAll() as $row) {
            $events[] = new SongEvent(
                id: (int) $row['id'],
                measureId: (int) $row['measure_id'],
                beat: (int) $row['beat'],
                elementType: $row['element_type'],
                elementId: (int) $row['element_id'],
                rootNote: (int) $row['root_note'],
                notes: $row['notes'],
                elementName: $row['element_name'],
                rootNoteName: $row['root_note_name'],
            );
        }

        return $events;
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO songs (name, bpm, tuning_id, time_signature_num, time_signature_den) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['name'],
            $data['bpm'],
            $data['tuning_id'] ?? 1,
            $data['time_signature_num'] ?? 4,
            $data['time_signature_den'] ?? 4,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM songs WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function createSection(int $songId, string $name, string $color): array
    {
        $position = $this->nextPosition('song_sections', 'song_id', $songId);

        $stmt = $this->db->prepare(
            'INSERT INTO song_sections (song_id, name, color, position) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$songId, $name, $color, $position]);

        return ['id' => (int) $this->db->lastInsertId(), 'position' => $position];
    }

    public function updateSection(int $id, array $fields): void
    {
        $this->updateById('song_sections', $id, $fields);
    }

    public function deleteSection(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM song_sections WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function createMeasure(int $sectionId): array
    {
        $position = $this->nextPosition('song_measures', 'section_id', $sectionId);

        $stmt = $this->db->prepare(
            'INSERT INTO song_measures (section_id, position) VALUES (?, ?)'
        );
        $stmt->execute([$sectionId, $position]);

        return ['id' => (int) $this->db->lastInsertId(), 'position' => $position];
    }

    public function deleteMeasure(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM song_measures WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function upsertEvent(array $data): int
    {
        $stmt = $this->db->prepare(
            'SELECT id FROM song_events WHERE measure_id = ? AND beat = ?'
        );
        $stmt->execute([$data['measure_id'], $data['beat']]);
        $existing = $stmt->fetch();

        if ($existing !== false) {
            $stmt = $this->db->prepare(
                'UPDATE song_events SET element_type = ?, element_id = ?, root_note = ?, notes = ? WHERE id = ?'
            );
            $stmt->execute([
                $data['element_type'],
                $data['element_id'],
                $data['root_note'],
                $data['notes'],
                $existing['id'],
            ]);

            return (int) $existing['id'];
        }

        $stmt = $this->db->prepare(
            'INSERT INTO song_events (measure_id, beat, element_type, element_id, root_note, notes) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $data['measure_id'],
            $data['beat'],
            $data['element_type'],
            $data['element_id'],
            $data['root_note'],
            $data['notes'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateEvent(int $id, array $fields): void
    {
        $this->updateById('song_events', $id, $fields);
    }

    public function deleteEvent(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM song_events WHERE id = ?');
        $stmt->execute([$id]);
    }

    private function nextPosition(string $table, string $parentColumn, int $parentId): int
    {
        $stmt = $this->db->prepare(
            sprintf('SELECT COALESCE(MAX(position), -1) + 1 AS next_pos FROM %s WHERE %s = ?', $table, $parentColumn)
        );
        $stmt->execute([$parentId]);

        return (int) $stmt->fetch()['next_pos'];
    }

    /**
     * Builds a dynamic UPDATE restricted to a safe whitelist of fields.
     *
     * @param array<string, int|string|null> $fields
     */
    private function updateById(string $table, int $id, array $fields): void
    {
        if ($fields === []) {
            return;
        }

        $assignments = [];
        $values = [];
        foreach ($fields as $column => $value) {
            $assignments[] = sprintf('%s = ?', $column);
            $values[] = $value;
        }
        $values[] = $id;

        $stmt = $this->db->prepare(sprintf('UPDATE %s SET %s WHERE id = ?', $table, implode(', ', $assignments)));
        $stmt->execute($values);
    }
}
