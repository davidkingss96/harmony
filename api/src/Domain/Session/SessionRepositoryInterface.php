<?php

declare(strict_types=1);

namespace App\Domain\Session;

interface SessionRepositoryInterface
{
    /** @return list<array{id: int, name: string, tuning_id: int, created_at: string, updated_at: string}> */
    public function findAll(): array;

    /** @return array{id: int, name: string, tuning_id: int, created_at: string, updated_at: string}|null */
    public function findById(int $id): ?array;

    public function create(string $name, int $tuningId): int;

    public function delete(int $id): void;

    /**
     * Items joined with reference/root names.
     *
     * @return list<array<string, int|string|null>>
     */
    public function findItems(int $sessionId): array;

    public function addItem(int $sessionId, string $type, int $referenceId, int $rootNote): int;

    public function findItemById(int $itemId): ?array;

    public function deleteItem(int $itemId): void;
}
