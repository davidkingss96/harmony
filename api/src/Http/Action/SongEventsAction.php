<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Application\SongService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SongEventsAction extends AbstractAction
{
    private const UPDATABLE_FIELDS = ['beat', 'element_type', 'element_id', 'root_note', 'notes'];

    public function __construct(private readonly SongService $songs)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        return match ($request->getMethod()) {
            'POST' => $this->upsert($request, $response, $args),
            'PUT' => $this->update($request, $response, $args),
            'DELETE' => $this->delete($request, $response, $args),
            default => $this->methodNotAllowed($response),
        };
    }

    private function upsert(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $this->parsedBody($request);
        // measure_id comes from the route (/measures/{id}/events) or the body (legacy)
        $routeMeasureId = $request->getAttribute('id');
        if ($routeMeasureId !== null && !isset($data['measure_id'])) {
            $data['measure_id'] = (int) $routeMeasureId;
        }

        return $this->json($response, $this->songs->upsertEvent($data), 201);
    }

    private function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->id($request, $args);
        if ($id === null) {
            return $this->json($response, ['error' => 'Event ID required'], 400);
        }

        $data = $this->parsedBody($request);
        $fields = [];
        foreach (self::UPDATABLE_FIELDS as $key) {
            if (array_key_exists($key, $data)) {
                $fields[$key] = $data[$key];
            }
        }

        $this->songs->updateEvent($id, $fields);

        return $this->json($response, ['message' => 'Event updated']);
    }

    private function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->id($request, $args);
        if ($id === null) {
            return $this->json($response, ['error' => 'Event ID required'], 400);
        }

        $this->songs->deleteEvent($id);

        return $this->json($response, ['message' => 'Event deleted']);
    }
}
