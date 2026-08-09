<?php

declare(strict_types=1);

namespace App\Http\Action;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Base for HTTP actions: shared JSON + parameter helpers.
 */
abstract class AbstractAction
{
    protected function json(ResponseInterface $response, array $data, int $status = 200): ResponseInterface
    {
        $response->getBody()->write((string) json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    /**
     * Resolves an "id" from route args, request attributes (slim-bridge) or
     * query params (legacy).
     */
    protected function id(ServerRequestInterface $request, array $args = []): ?int
    {
        $id = $args['id']
            ?? $request->getAttribute('id')
            ?? $request->getQueryParams()['id']
            ?? null;

        return $id === null ? null : (int) $id;
    }

    /** @return array<string, mixed> */
    protected function parsedBody(ServerRequestInterface $request): array
    {
        $body = $request->getParsedBody();

        return is_array($body) ? $body : [];
    }

    protected function methodNotAllowed(ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, ['error' => 'Method not allowed'], 405);
    }
}
