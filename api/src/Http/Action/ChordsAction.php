<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Domain\Music\ChordRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ChordsAction extends AbstractAction
{
    public function __construct(private readonly ChordRepositoryInterface $chords)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, $this->chords->findAllChords());
    }
}
