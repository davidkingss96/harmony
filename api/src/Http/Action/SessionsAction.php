<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Application\SessionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SessionsAction extends AbstractAction
{
    public function __construct(private readonly SessionService $sessions)
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

        return $id !== null
            ? $this->json($response, $this->sessions->get($id))
            : $this->json($response, $this->sessions->list());
    }

    private function create(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = $this->parsedBody($request);

        return $this->json(
            $response,
            $this->sessions->create(
                (string) ($data['name'] ?? ''),
                (int) ($data['tuning_id'] ?? 0)
            ),
            201
        );
    }

    private function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->id($request, $args);
        if ($id === null) {
            return $this->json($response, ['error' => 'Session ID required'], 400);
        }

        $this->sessions->delete($id);

        return $this->json($response, ['message' => 'Session deleted']);
    }
}
