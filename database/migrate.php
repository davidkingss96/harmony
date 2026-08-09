#!/usr/bin/env php
<?php

declare(strict_types=1);

use App\Infrastructure\Database\PdoFactory;

require __DIR__ . '/../api/vendor/autoload.php';

$pdo = (new PdoFactory(
    getenv('DB_HOST') ?: 'mysql',
    getenv('DB_NAME') ?: 'harmony',
    getenv('DB_USER') ?: 'harmony_user',
    getenv('DB_PASS') ?: 'harmony_pass',
))->create();

$pdo->exec(
    'CREATE TABLE IF NOT EXISTS schema_migrations (
        version VARCHAR(255) PRIMARY KEY,
        applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )'
);

$migrationsDir = __DIR__ . '/migrations';
$applied = $pdo->query('SELECT version FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);

$files = glob($migrationsDir . '/*.sql');
sort($files);

$exit = 0;
foreach ($files as $file) {
    $version = basename($file, '.sql');

    if (in_array($version, $applied, true)) {
        echo sprintf("SKIP  %s (already applied)\n", $version);
        continue;
    }

    $sql = file_get_contents($file);

    try {
        // Note: MySQL auto-commits DDL, so this runs without an explicit transaction.
        $pdo->exec($sql);
        $stmt = $pdo->prepare('INSERT INTO schema_migrations (version) VALUES (?)');
        $stmt->execute([$version]);
        echo sprintf("APPLY %s\n", $version);
    } catch (Throwable $e) {
        echo sprintf("ERROR %s: %s\n", $version, $e->getMessage());
        $exit = 1;
        break;
    }
}

echo $exit === 0 ? "Migrations up to date.\n" : "Migrations finished with errors.\n";
exit($exit);
