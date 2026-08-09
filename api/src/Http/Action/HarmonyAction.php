<?php

declare(strict_types=1);

namespace App\Http\Action;

use App\Application\HeatmapService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HarmonyAction extends AbstractAction
{
    public function __construct(private readonly HeatmapService $heatmap)
    {
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        if ($request->getMethod() !== 'POST') {
            return $this->methodNotAllowed($response);
        }

        $data = $this->parsedBody($request);

        return $this->json(
            $response,
            $this->heatmap->calculate(
                (int) ($data['tuning_id'] ?? 0),
                is_array($data['items'] ?? null) ? $data['items'] : []
            )
        );
    }
}
