<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Application\SongService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SongMeasuresAction extends AbstractAction
{
    public function __construct(private readonly SongService $songs)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args = []): ResponseInterface
    {
        return match ($request->getMethod()) {
            'POST' => $this->create($request, $response, $args),
            'DELETE' => $this->delete($request, $response, $args),
            default => $this->methodNotAllowed($response),
        };
    }

    private function create(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = $this->parsedBody($request);
        // section_id comes from the route (/sections/{id}/measures) or the body (legacy)
        $sectionId = $request->getAttribute('id') ?? $data['section_id'] ?? 0;

        return $this->json(
            $response,
            $this->songs->addMeasure((int) $sectionId),
            201
        );
    }

    private function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->id($request, $args);
        if ($id === null) {
            return $this->json($response, ['error' => 'Measure ID required'], 400);
        }

        $this->songs->deleteMeasure($id);

        return $this->json($response, ['message' => 'Measure deleted']);
    }
}
