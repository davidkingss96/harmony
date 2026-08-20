<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AuthMiddleware implements MiddlewareInterface
{
    private const PUBLIC_ROUTES = [
        ['GET', '/api/notes'],
        ['GET', '/api/chords'],
        ['GET', '/api/scales'],
        ['GET', '/api/tunings'],
    ];

    public function __construct(
        private \PDO $db
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $method = $request->getMethod();
        $path = $request->getUri()->getPath();

        foreach (self::PUBLIC_ROUTES as [$routeMethod, $routePath]) {
            if ($method === $routeMethod && $path === $routePath) {
                return $handler->handle($request);
            }
        }

        $apiKey = $request->getHeaderLine('X-API-Key');

        if ($apiKey === '') {
            return $this->unauthorizedResponse('Missing X-API-Key header');
        }

        $stmt = $this->db->prepare('SELECT id FROM api_keys WHERE key_hash = ? AND active = 1');
        $stmt->execute([hash('sha256', $apiKey)]);

        if ($stmt->fetch() === false) {
            return $this->unauthorizedResponse('Invalid or inactive API key');
        }

        return $handler->handle($request);
    }

    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new \Slim\Psr7\Response();
        $response->getBody()->write((string) json_encode(['error' => $message]));
        return $response
            ->withStatus(401)
            ->withHeader('Content-Type', 'application/json');
    }
}
