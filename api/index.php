<?php

declare(strict_types=1);

use App\Http\Action\ChordsAction;
use App\Http\Action\HarmonyAction;
use App\Http\Action\NotesAction;
use App\Http\Action\ScalesAction;
use App\Http\Action\SessionItemsAction;
use App\Http\Action\SessionsAction;
use App\Http\Action\SongEventsAction;
use App\Http\Action\SongMeasuresAction;
use App\Http\Action\SongSectionsAction;
use App\Http\Action\SongsAction;
use App\Http\Action\TuningsAction;
use App\Http\Middleware\CorsMiddleware;
use App\Http\Middleware\JsonErrorHandler;
use DI\Bridge\Slim\Bridge;
use DI\ContainerBuilder;

require __DIR__ . '/vendor/autoload.php';

$builder = new ContainerBuilder();
$builder->addDefinitions(__DIR__ . '/config/settings.php');
$container = $builder->build();

$app = Bridge::create($container);

// Middleware (last added runs first: Cors outermost, then routing, then body parsing)
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$app->add(CorsMiddleware::class);

$errorMiddleware = $app->addErrorMiddleware((bool) $container->get('app.debug'), true, true);
$errorMiddleware->setDefaultErrorHandler(JsonErrorHandler::class);

// --- Catalog ---
$app->get('/api/notes', NotesAction::class);
$app->get('/api/chords', ChordsAction::class);
$app->get('/api/scales', ScalesAction::class);
$app->get('/api/tunings', TuningsAction::class);

// --- Sessions ---
$app->get('/api/sessions', SessionsAction::class);
$app->post('/api/sessions', SessionsAction::class);
$app->get('/api/sessions/{id}', SessionsAction::class);
$app->delete('/api/sessions/{id}', SessionsAction::class);
$app->post('/api/sessions/{id}/items', SessionItemsAction::class);
$app->delete('/api/session-items/{id}', SessionItemsAction::class);

// --- Harmony ---
$app->post('/api/harmony', HarmonyAction::class);

// --- Songs ---
$app->get('/api/songs', SongsAction::class);
$app->post('/api/songs', SongsAction::class);
$app->get('/api/songs/{id}', SongsAction::class);
$app->delete('/api/songs/{id}', SongsAction::class);
$app->post('/api/songs/{id}/sections', SongSectionsAction::class);

// --- Song sections ---
$app->post('/api/song-sections', SongSectionsAction::class);
$app->put('/api/song-sections/{id}', SongSectionsAction::class);
$app->delete('/api/song-sections/{id}', SongSectionsAction::class);

// --- Song measures ---
$app->post('/api/song-measures', SongMeasuresAction::class);
$app->post('/api/sections/{id}/measures', SongMeasuresAction::class);
$app->delete('/api/song-measures/{id}', SongMeasuresAction::class);

// --- Song events ---
$app->post('/api/song-events', SongEventsAction::class);
$app->post('/api/measures/{id}/events', SongEventsAction::class);
$app->put('/api/song-events/{id}', SongEventsAction::class);
$app->delete('/api/song-events/{id}', SongEventsAction::class);

$app->run();
