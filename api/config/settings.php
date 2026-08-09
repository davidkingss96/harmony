<?php

declare(strict_types=1);

use App\Domain\Music\ChordRepositoryInterface;
use App\Domain\Music\NoteRepositoryInterface;
use App\Domain\Music\ScaleRepositoryInterface;
use App\Domain\Music\TuningRepositoryInterface;
use App\Domain\Session\SessionRepositoryInterface;
use App\Domain\Song\SongRepositoryInterface;
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

return [
    // Application settings
    'app.debug' => env('APP_DEBUG', false),

    // PSR-17 response factory (required by the JSON error handler)
    ResponseFactoryInterface::class => new ResponseFactory(),

    // Database connection factory (scalar params bound explicitly)
    PdoFactory::class => autowire()
        ->constructorParameter('host', env('DB_HOST', 'mysql'))
        ->constructorParameter('name', env('DB_NAME', 'harmony'))
        ->constructorParameter('user', env('DB_USER', 'harmony_user'))
        ->constructorParameter('pass', env('DB_PASS', 'harmony_pass')),

    PDO::class => factory(static fn (PdoFactory $factory): PDO => $factory->create()),

    // Repository bindings (DIP: consumers depend on interfaces)
    NoteRepositoryInterface::class => get(MySqlCatalogRepository::class),
    ChordRepositoryInterface::class => get(MySqlCatalogRepository::class),
    ScaleRepositoryInterface::class => get(MySqlCatalogRepository::class),
    TuningRepositoryInterface::class => get(MySqlCatalogRepository::class),
    SessionRepositoryInterface::class => get(MySqlSessionRepository::class),
    SongRepositoryInterface::class => get(MySqlSongRepository::class),
];
