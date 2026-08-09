<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

/**
 * Base repository providing a shared PDO handle.
 */
abstract class MySqlRepository
{
    public function __construct(protected readonly PDO $db)
    {
    }
}
