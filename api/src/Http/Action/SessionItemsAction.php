<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Application\SessionService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SessionItemsAction extends AbstractAction
{
    public function __construct(private readonly SessionService $sessions)
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
        // session_id comes from the route (/sessions/{id}/items) or the body (legacy)
        $sessionId = $request->getAttribute('id') ?? $data['session_id'] ?? 0;

        return $this->json(
            $response,
            $this->sessions->addItem(
                (int) $sessionId,
                (string) ($data['type'] ?? ''),
                (int) ($data['reference_id'] ?? 0),
                (int) ($data['root_note'] ?? 0)
            ),
            201
        );
    }

    private function delete(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $this->id($request, $args);
        if ($id === null) {
            return $this->json($response, ['error' => 'Session item ID required'], 400);
        }

        $this->sessions->deleteItem($id);

        return $this->json($response, ['message' => 'Session item deleted']);
    }
}
