<?php

declare(strict_types=1);

namespace App\Application;

use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use App\Domain\Song\Song;
use App\Domain\Song\SongEvent;
use App\Domain\Song\SongMeasure;
use App\Domain\Song\SongRepositoryInterface;
use App\Domain\Song\SongSection;

final class SongService
{
    public function __construct(
        private readonly SongRepositoryInterface $songs,
        private readonly HeatmapService $heatmap,
    ) {
    }

    public function list(): array
    {
        return $this->songs->findAll();
    }

    public function get(int $id, bool $withPlayerData = false): array
    {
        $song = $this->songs->findById($id);
        if ($song === null) {
            throw new NotFoundException('Song not found');
        }

        $result = $this->serialize($song);

        if ($withPlayerData) {
            $result['player_data'] = $this->buildPlayerData($song);
        }

        return $result;
    }

    /**
     * @param array{name: string, bpm: float, tuning_id?: int,
     *             time_signature_num?: int, time_signature_den?: int} $data
     *
     * @return array{id: int, name: string, bpm: float, tuning_id: int,
     *               time_signature_num: int, time_signature_den: int}
     */
    public function create(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $bpm = (float) ($data['bpm'] ?? 0);

        if ($name === '') {
            throw new ValidationException(['name is required']);
        }
        if ($bpm <= 0) {
            throw new ValidationException(['bpm must be a positive number']);
        }

        $song = [
            'name' => $name,
            'bpm' => $bpm,
            'tuning_id' => (int) ($data['tuning_id'] ?? 1),
            'time_signature_num' => (int) ($data['time_signature_num'] ?? 4),
            'time_signature_den' => (int) ($data['time_signature_den'] ?? 4),
        ];

        $id = $this->songs->create($song);

        return ['id' => $id] + $song;
    }

    public function delete(int $id): void
    {
        $this->songs->delete($id);
    }

    /**
     * @return array{id: int, song_id: int, name: string, color: string, position: int}
     */
    public function addSection(int $songId, string $name, string $color): array
    {
        $name = trim($name);
        if ($name === '') {
            throw new ValidationException(['name is required']);
        }

        $created = $this->songs->createSection($songId, $name, $color);

        return [
            'id' => $created['id'],
            'song_id' => $songId,
            'name' => $name,
            'color' => $color,
            'position' => $created['position'],
        ];
    }

    /**
     * @param array<string, int|string> $fields
     */
    public function updateSection(int $id, array $fields): void
    {
        $this->songs->updateSection($id, $fields);
    }

    public function deleteSection(int $id): void
    {
        $this->songs->deleteSection($id);
    }

    /**
     * @return array{id: int, section_id: int, position: int}
     */
    public function addMeasure(int $sectionId): array
    {
        $created = $this->songs->createMeasure($sectionId);

        return [
            'id' => $created['id'],
            'section_id' => $sectionId,
            'position' => $created['position'],
        ];
    }

    public function deleteMeasure(int $id): void
    {
        $this->songs->deleteMeasure($id);
    }

    /**
     * @param array{measure_id: int, beat: int, element_type: string,
     *             element_id: int, root_note: int, notes?: string|null} $data
     *
     * @return array{id: int, measure_id: int, beat: int, element_type: string,
     *               element_id: int, root_note: int, notes: string|null}
     */
    public function upsertEvent(array $data): array
    {
        $required = ['measure_id', 'beat', 'element_type', 'element_id', 'root_note'];
        $errors = [];
        foreach ($required as $key) {
            if (!isset($data[$key]) || $data[$key] === '') {
                $errors[] = $key . ' is required';
            }
        }
        if ($errors !== []) {
            throw new ValidationException($errors);
        }
        if (!in_array($data['element_type'], [SongEvent::TYPE_CHORD, SongEvent::TYPE_SCALE], true)) {
            throw new ValidationException(['element_type must be CHORD or SCALE']);
        }

        $id = $this->songs->upsertEvent([
            'measure_id' => (int) $data['measure_id'],
            'beat' => (int) $data['beat'],
            'element_type' => $data['element_type'],
            'element_id' => (int) $data['element_id'],
            'root_note' => (int) $data['root_note'],
            'notes' => isset($data['notes']) && $data['notes'] !== '' ? $data['notes'] : null,
        ]);

        return [
            'id' => $id,
            'measure_id' => (int) $data['measure_id'],
            'beat' => (int) $data['beat'],
            'element_type' => $data['element_type'],
            'element_id' => (int) $data['element_id'],
            'root_note' => (int) $data['root_note'],
            'notes' => $data['notes'] ?? null,
        ];
    }

    /**
     * @param array<string, int|string|null> $fields
     */
    public function updateEvent(int $id, array $fields): void
    {
        $this->songs->updateEvent($id, $fields);
    }

    public function deleteEvent(int $id): void
    {
        $this->songs->deleteEvent($id);
    }

    private function serialize(Song $song): array
    {
        return [
            'id' => $song->id,
            'name' => $song->name,
            'bpm' => $song->bpm,
            'tuning_id' => $song->tuningId,
            'time_signature_num' => $song->timeSignatureNum,
            'time_signature_den' => $song->timeSignatureDen,
            'created_at' => $song->createdAt,
            'updated_at' => $song->updatedAt,
            'sections' => array_map(
                static fn (SongSection $section): array => [
                    'id' => $section->id,
                    'song_id' => $section->songId,
                    'name' => $section->name,
                    'color' => $section->color,
                    'position' => $section->position,
                    'measures' => array_map(
                        static fn (SongMeasure $measure): array => [
                            'id' => $measure->id,
                            'section_id' => $measure->sectionId,
                            'position' => $measure->position,
                            'events' => array_map(
                                static fn (SongEvent $event): array => [
                                    'id' => $event->id,
                                    'measure_id' => $event->measureId,
                                    'beat' => $event->beat,
                                    'element_type' => $event->elementType,
                                    'element_id' => $event->elementId,
                                    'root_note' => $event->rootNote,
                                    'notes' => $event->notes,
                                    'element_name' => $event->elementName,
                                    'root_note_name' => $event->rootNoteName,
                                ],
                                $measure->events
                            ),
                        ],
                        $section->measures
                    ),
                ],
                $song->sections
            ),
        ];
    }

    private function buildPlayerData(Song $song): array
    {
        $playerMeasures = [];
        $index = 0;

        foreach ($song->sections as $section) {
            foreach ($section->measures as $measure) {
                $events = [];
                foreach ($measure->events as $event) {
                    $heatmap = $this->heatmap->calculate($song->tuningId, [[
                        'type' => $event->elementType,
                        'reference_id' => $event->elementId,
                        'root_note' => $event->rootNote,
                    ]]);

                    $events[] = [
                        'beat' => $event->beat,
                        'element_type' => $event->elementType,
                        'element_name' => $event->elementName,
                        'root_note_name' => $event->rootNoteName,
                        'root_note' => $event->rootNote,
                        'notes' => $event->notes,
                        'heatmap' => $heatmap['heatmap'],
                        'influence' => $heatmap['influence'],
                    ];
                }

                $playerMeasures[] = [
                    'global_index' => $index,
                    'section_name' => $section->name,
                    'section_color' => $section->color,
                    'events' => $events,
                ];
                $index++;
            }
        }

        return [
            'bpm' => $song->bpm,
            'time_signature_num' => $song->timeSignatureNum,
            'time_signature_den' => $song->timeSignatureDen,
            'total_measures' => $index,
            'measures' => $playerMeasures,
        ];
    }
}
