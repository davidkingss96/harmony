<?php

declare(strict_types=1);

use App\Domain\Music\ChordRepositoryInterface;
use App\Domain\Music\NoteRepositoryInterface;
use App\Domain\Music\ScaleRepositoryInterface;
use App\Domain\Music\TuningRepositoryInterface;
use App\Domain\Session\SessionRepositoryInterface;
use App\Domain\Song\SongRepositoryInterface;
use App\Http\Middleware\AuthMiddleware;
use App\Infrastructure\Database\PdoFactory;
use App\Infrastructure\Repositories\MySqlCatalogRepository;
use App\Infrastructure\Repositories\MySqlSessionRepository;
use App\Infrastructure\Repositories\MySqlSongRepository;
use Psr\Http\Message\ResponseFactoryInterface;
use Slim\Psr7\Factory\ResponseFactory;

use function DI\autowire;
use function DI\env;
use function DI\factory;
use function DI\get;

function requireEnv(string $name): string {
    $value = getenv($name);
    if ($value === false || $value === '') {
        throw new \RuntimeException("Missing required environment variable: $name");
    }
    return $value;
}

return [
    // Application settings
    'app.debug' => filter_var(env('APP_DEBUG', false), FILTER_VALIDATE_BOOLEAN),

    // PSR-17 response factory (required by the JSON error handler)
    ResponseFactoryInterface::class => new ResponseFactory(),

    // Database connection factory (scalar params bound explicitly)
    PdoFactory::class => autowire()
        ->constructorParameter('host', requireEnv('DB_HOST'))
        ->constructorParameter('name', requireEnv('DB_NAME'))
        ->constructorParameter('user', requireEnv('DB_USER'))
        ->constructorParameter('pass', requireEnv('DB_PASS')),

    PDO::class => factory(static fn (PdoFactory $factory): PDO => $factory->create()),

    // Auth middleware
    AuthMiddleware::class => autowire(),

    // Repository bindings (DIP: consumers depend on interfaces)
    NoteRepositoryInterface::class => get(MySqlCatalogRepository::class),
    ChordRepositoryInterface::class => get(MySqlCatalogRepository::class),
    ScaleRepositoryInterface::class => get(MySqlCatalogRepository::class),
    TuningRepositoryInterface::class => get(MySqlCatalogRepository::class),
    SessionRepositoryInterface::class => get(MySqlSessionRepository::class),
    SongRepositoryInterface::class => get(MySqlSongRepository::class),
];
