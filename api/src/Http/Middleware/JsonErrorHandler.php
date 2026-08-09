<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Exception\NotFoundException;
use App\Domain\Exception\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Handlers\ErrorHandler;

/**
 * Converts exceptions to consistent JSON responses without leaking
 * internal details in production.
 */
final class JsonErrorHandler extends ErrorHandler
{
    protected function respond(): ResponseInterface
    {
        $exception = $this->exception;

        // OPTIONS preflight: respond 200 with the Allow header (no body needed).
        if ($this->method === 'OPTIONS' && $exception instanceof HttpMethodNotAllowedException) {
            return $this->responseFactory
                ->createResponse(200)
                ->withHeader('Allow', implode(', ', $exception->getAllowedMethods()))
                ->withHeader('Content-Type', 'application/json');
        }

        if ($exception instanceof NotFoundException) {
            $status = 404;
            $payload = ['error' => $exception->getMessage()];
        } elseif ($exception instanceof ValidationException) {
            $status = 400;
            $payload = ['error' => 'Validation failed', 'errors' => $exception->errors()];
        } elseif ($exception instanceof HttpException) {
            $status = $exception->getCode();
            $payload = ['error' => $exception->getMessage()];
        } else {
            $status = 500;
            $payload = ['error' => 'Internal server error'];

            if ($this->displayErrorDetails) {
                $payload['message'] = $exception->getMessage();
            }
        }

        $response = $this->responseFactory->createResponse($status);
        $response->getBody()->write((string) json_encode($payload));

        return $response->withHeader('Content-Type', 'application/json');
    }
}
