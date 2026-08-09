<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Application\SongService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SongSectionsAction extends AbstractAction
{
    private const UPDATABLE_FIELDS = ['name', 'color', 'position'];

    public function __construct(private readonly SongService $songs)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        return match ($request->getMethod()) {
            'POST' => $this->create($request, $response, $args),
            'PUT' => $this->update($request, $response, $args),
            'DELETE' => $this->delete($request, $response, $args),
            default => $this->methodNotAllowed($response),
        };
    }

    private function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $this->parsedBody($request);
        // song_id comes from the route (/songs/{id}/sections) or the body (legacy)
        $songId = $request->getAttribute('id') ?? $data['song_id'] ?? 0;

        return $this->json(
            $response,
            $this->songs->addSection(
                (int) $songId,
                (string) ($data['name'] ?? ''),
                (string) ($data['color'] ?? '#e94560')
            ),
            201
        );
    }

    private function update(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->id($request, $args);
        if ($id === null) {
            return $this->json($response, ['error' => 'Section ID required'], 400);
        }

        $data = $this->parsedBody($request);
        $fields = array_intersect_key($data, array_flip(self::UPDATABLE_FIELDS));

        $this->songs->updateSection($id, $fields);

        return $this->json($response, ['message' => 'Section updated']);
    }

    private function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->id($request, $args);
        if ($id === null) {
            return $this->json($response, ['error' => 'Section ID required'], 400);
        }

        $this->songs->deleteSection($id);

        return $this->json($response, ['message' => 'Section deleted']);
    }
}
