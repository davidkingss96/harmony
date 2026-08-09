<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Domain\Music\NoteRepositoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class NotesAction extends AbstractAction
{
    public function __construct(private readonly NoteRepositoryInterface $notes)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return $this->json($response, $this->notes->findAllNotes());
    }
}
