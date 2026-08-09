<?php

declare(strict_types=1);

namespace App\Infrastructure\Database;

use PDO;

final class PdoFactory
{
    public function __construct(
        private string $host,
        private string $name,
        private string $user,
        private string $pass,
    ) {
    }

    public function create(): PDO
    {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $this->host, $this->name);

        return new PDO($dsn, $this->user, $this->pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
