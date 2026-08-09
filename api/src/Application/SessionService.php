<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Session\SessionItem;
use App\Domain\Session\SessionRepositoryInterface;

final class SessionService
{
    public function __construct(private readonly SessionRepositoryInterface $sessions)
    {
    }

    public function list(): array
    {
        return $this->sessions->findAll();
    }

    public function get(int $id): array
    {
        $session = $this->sessions->findById($id);
        if ($session === null) {
            throw new NotFoundException('Session not found');
        }

        $session['items'] = $this->sessions->findItems($id);

        return $session;
    }

    /**
     * @return array{id: int, name: string, tuning_id: int}
     */
    public function create(string $name, int $tuningId): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new ValidationException(['name is required']);
        }
        if ($tuningId < 1) {
            throw new ValidationException(['tuning_id is required']);
        }

        return [
            'id' => $this->sessions->create($name, $tuningId),
            'name' => $name,
            'tuning_id' => $tuningId,
        ];
    }

    public function delete(int $id): void
    {
        $this->sessions->delete($id);
    }

    /**
     * @return array{id: int, session_id: int, type: string, reference_id: int, root_note: int}
     */
    public function addItem(int $sessionId, string $type, int $referenceId, int $rootNote): array
    {
        if (!in_array($type, [SessionItem::TYPE_CHORD, SessionItem::TYPE_SCALE], true)) {
            throw new ValidationException(['type must be CHORD or SCALE']);
        }
        if ($referenceId < 1) {
            throw new ValidationException(['reference_id is required']);
        }
        if ($rootNote < 0 || $rootNote > 11) {
            throw new ValidationException(['root_note must be 0-11']);
        }

        $id = $this->sessions->addItem($sessionId, $type, $referenceId, $rootNote);

        return [
            'id' => $id,
            'session_id' => $sessionId,
            'type' => $type,
            'reference_id' => $referenceId,
            'root_note' => $rootNote,
        ];
    }

    public function deleteItem(int $itemId): void
    {
        if ($this->sessions->findItemById($itemId) === null) {
            throw new NotFoundException('Session item not found');
        }

        $this->sessions->deleteItem($itemId);
    }
}
