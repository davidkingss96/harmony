# 07 — API

REST bajo `/api`. Respuestas JSON. Las Actions se resuelven por DI ([[02-HTTP-Layer]]).

## Catálogo

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/notes` | 12 notas |
| GET | `/api/chords` | acordes (name + formula) |
| GET | `/api/scales` | escalas (name + formula) |
| GET | `/api/tunings` | afinaciones |

## Sesiones

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/sessions` | listar |
| POST | `/api/sessions` | crear `{name, tuning_id}` → 201 |
| GET | `/api/sessions/{id}` | detalle con `items` (nombre del elemento resuelto) |
| DELETE | `/api/sessions/{id}` | eliminar (cascade) |
| POST | `/api/sessions/{id}/items` | añadir `{type: CHORD/SCALE, reference_id, root_note}` → 201 |
| DELETE | `/api/session-items/{id}` | eliminar ítem |

## Harmony

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/harmony` | body `{tuning_id, items:[{type, reference_id, root_note}]}` → `{heatmap, influence}` |

## Songs (Song Builder)

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/songs` | listar |
| POST | `/api/songs` | crear `{name, bpm, tuning_id, time_signature_*}` → 201 |
| GET | `/api/songs/{id}` | detalle; con `?player=1` añade `player_data` (heatmaps por compás) |
| DELETE | `/api/songs/{id}` | eliminar (cascade) |
| POST | `/api/songs/{id}/sections` | añadir sección `{name, color}` → 201 |
| PUT / DELETE | `/api/song-sections/{id}` | actualizar / eliminar sección |
| POST | `/api/sections/{id}/measures` | añadir compás → 201 |
| DELETE | `/api/song-measures/{id}` | eliminar compás |
| POST | `/api/measures/{id}/events` | upsert evento `{beat, element_type, element_id, root_note, notes}` → 201 |
| PUT / DELETE | `/api/song-events/{id}` | actualizar / eliminar evento |

## Convenciones

- **201** en creación, **200** en el resto; **404**/**405**/**400**/**500** según error (JSON `{error}`).
- Preflight `OPTIONS` → **200** con `Allow` (manejado por `CorsMiddleware`).
- CORS habilitado para consumo cross-origin (`Access-Control-Allow-*`).

> Ejemplos y flujos del cliente en [[08-Frontend]].
