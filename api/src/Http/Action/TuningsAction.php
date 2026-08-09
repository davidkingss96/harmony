<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Domain\Music\TuningRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class TuningsAction extends AbstractAction
{
    public function __construct(private readonly TuningRepositoryInterface $tunings)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, $this->tunings->findAllTunings());
    }
}
