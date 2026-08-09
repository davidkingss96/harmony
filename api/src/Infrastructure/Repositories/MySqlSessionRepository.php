<?php

declare(strict_types=1);

namespace App\Infrastructure\Repositories;

use App\Domain\Session\SessionRepositoryInterface;
use App\Infrastructure\Database\MySqlRepository;

final class MySqlSessionRepository extends MySqlRepository implements SessionRepositoryInterface
{
    public function findAll(): array
    {
        return $this->db->query('SELECT * FROM sessions ORDER BY created_at DESC')->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM sessions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function create(string $name, int $tuningId): int
    {
        $stmt = $this->db->prepare('INSERT INTO sessions (name, tuning_id) VALUES (?, ?)');
        $stmt->execute([$name, $tuningId]);

        return (int) $this->db->lastInsertId();
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE id = ?');
        $stmt->execute([$id]);
    }

    public function findItems(int $sessionId): array
    {
        $sql = <<<'SQL'
            SELECT si.*,
                   CASE
                       WHEN si.type = 'CHORD' THEN c.name
                       WHEN si.type = 'SCALE' THEN s.name
                   END AS reference_name,
                   n.name AS root_note_name
            FROM session_items si
            LEFT JOIN chords c ON si.type = 'CHORD' AND si.reference_id = c.id
            LEFT JOIN scales s ON si.type = 'SCALE' AND si.reference_id = s.id
            LEFT JOIN notes n ON si.root_note = n.chromatic_position
            WHERE si.session_id = ?
            ORDER BY si.position
        SQL;

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$sessionId]);

        return $stmt->fetchAll();
    }

    public function addItem(int $sessionId, string $type, int $referenceId, int $rootNote): int
    {
        $stmt = $this->db->prepare('SELECT COALESCE(MAX(position), -1) + 1 AS next_pos FROM session_items WHERE session_id = ?');
        $stmt->execute([$sessionId]);
        $position = (int) $stmt->fetch()['next_pos'];

        $stmt = $this->db->prepare(
            'INSERT INTO session_items (session_id, type, reference_id, root_note, position) VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$sessionId, $type, $referenceId, $rootNote, $position]);

        return (int) $this->db->lastInsertId();
    }

    public function findItemById(int $itemId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM session_items WHERE id = ?');
        $stmt->execute([$itemId]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    public function deleteItem(int $itemId): void
    {
        $stmt = $this->db->prepare('DELETE FROM session_items WHERE id = ?');
        $stmt->execute([$itemId]);
    }
}
