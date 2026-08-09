<?php

declare(strict_types=1);

namespace App\Domain\Song;

interface SongRepositoryInterface
{
    /** @return list<array{id: int, name: string, bpm: float}> */
    public function findAll(): array;

    public function findById(int $id): ?Song;

    /**
     * @param array{name: string, bpm: float, tuning_id?: int,
     *             time_signature_num?: int, time_signature_den?: int} $data
     */
    public function create(array $data): int;

    public function delete(int $id): void;

    /** @return array{id: int, position: int} */
    public function createSection(int $songId, string $name, string $color): array;

    /** @param array<string, int|string> $fields */
    public function updateSection(int $id, array $fields): void;

    public function deleteSection(int $id): void;

    /** @return array{id: int, position: int} */
    public function createMeasure(int $sectionId): array;

    public function deleteMeasure(int $id): void;

    /**
     * @param array{measure_id: int, beat: int, element_type: string,
     *             element_id: int, root_note: int, notes: string|null} $data
     */
    public function upsertEvent(array $data): int;

    /** @param array<string, int|string|null> $fields */
    public function updateEvent(int $id, array $fields): void;

    public function deleteEvent(int $id): void;
}
