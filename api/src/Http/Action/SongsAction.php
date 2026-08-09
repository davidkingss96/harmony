<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Application\SongService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SongsAction extends AbstractAction
{
    public function __construct(private readonly SongService $songs)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        return match ($request->getMethod()) {
            'GET' => $this->index($request, $response, $args),
            'POST' => $this->create($request, $response),
            'DELETE' => $this->delete($request, $response, $args),
            default => $this->methodNotAllowed($response),
        };
    }

    private function index(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->id($request, $args);
        if ($id === null) {
            return $this->json($response, $this->songs->list());
        }

        $withPlayerData = ($request->getQueryParams()['player'] ?? '') === '1';

        return $this->json($response, $this->songs->get($id, $withPlayerData));
    }

    private function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->parsedBody($request);

        return $this->json(
            $response,
            $this->songs->create([
                'name' => (string) ($data['name'] ?? ''),
                'bpm' => (float) ($data['bpm'] ?? 0),
                'tuning_id' => isset($data['tuning_id']) ? (int) $data['tuning_id'] : null,
                'time_signature_num' => isset($data['time_signature_num']) ? (int) $data['time_signature_num'] : null,
                'time_signature_den' => isset($data['time_signature_den']) ? (int) $data['time_signature_den'] : null,
            ]),
            201
        );
    }

    private function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->id($request, $args);
        if ($id === null) {
            return $this->json($response, ['error' => 'Song ID required'], 400);
        }

        $this->songs->delete($id);

        return $this->json($response, ['message' => 'Song deleted']);
    }
}
