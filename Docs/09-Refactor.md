# 09 — Refactor

Historia de la migración del API: **PHP procedural → Slim 4 en capas**.

## Antes (legacy)

- Un único `api/index.php` procedural con dispatch manual por query string: `?endpoint=X&id=Y`.
- Lógica (música, BD, HTTP) acoplada en el mismo archivo; sin testabilidad, sin DI.
- El frontend llamaba `fetch('/api/index.php?endpoint=...')`.

## Después (Slim 4)

- **Rutas REST** por método y ruta (`/api/songs/{id}`), resueltas por el router.
- **Capas**: HTTP (Actions/Middleware) → Application (Services) → Domain (entidades + interfaces) → Infrastructure (repositorios PDO).
- **PHP-DI**: autowiring + bindings interfaz→implementación; conexión vía env.
- **Errores JSON** consistentes (404/405/400/500) y CORS centralizado.

## Fases

| Fase | Qué se hizo |
|---|---|
| **1 — API limpio + shim** | Se crearon Actions, Services, Domain, Repositorios y el schema. Se mantuvo `LegacyAction` ([[02-HTTP-Layer]]) despachando `?endpoint=X` a las mismas Actions, para no romper el frontend. |
| **2 — Frontend migrado** | `app.js`: `API_BASE = '/api'` y las 21 llamadas `?endpoint=...` → rutas REST (IDs en la ruta, p.ej. `/measures/{id}/events`). Se eliminó `LegacyAction` y la ruta `/api/index.php`. |

## Bugs resueltos en la migración

- **OPTIONS preflight**: Slim 4.15 no lo auto-responde → `CorsMiddleware` hace short-circuit (200 + CORS).
- **Status de errores**: el handler custom devolvía 500 para todo → mapeo de `HttpException`/`NotFoundException`/`ValidationException`.
- **IDs de ruta**: slim-bridge inyecta placeholders como *atributos* del request, no como `$args` → `$request->getAttribute('id')` ([[02-HTTP-Layer]]).
- **IDs anidados**: `session_id`/`song_id`/`section_id`/`measure_id` ahora provienen de la ruta (fallback a body para compatibilidad).

## Estado actual

- API REST completo ([[07-API]]), frontend 100% migrado, sin shim legacy.
- Migraciones idempotentes (`database/migrate.php`).
