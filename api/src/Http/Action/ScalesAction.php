<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Domain\Music\ScaleRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ScalesAction extends AbstractAction
{
    public function __construct(private readonly ScaleRepositoryInterface $scales)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, $this->scales->findAllScales());
    }
}
