<?php

declare(strict_types=1);

namespace App\Domain\Exception;

/**
 * Thrown when a requested resource does not exist (maps to HTTP 404).
 */
final class NotFoundException extends \RuntimeException
{
}
