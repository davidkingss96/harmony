# 01 — Arquitectura

## Resumen

API **monolítico en capas** con dependencia invertida (DIP): el dominio define interfaces y la infraestructura las implementa. El contenedor **PHP-DI** (vía `php-di/slim-bridge`) ensambla todo por autowiring + bindings explícitos en `config/settings.php`.

## Capas

```mermaid
graph TD
    subgraph HTTP["02-HTTP-Layer"]
        R[Rutas REST - index.php]
        A[Actions]
        M[Middleware: CORS / ErrorHandler]
    end
    subgraph APP["03-Application-Layer"]
        S[SongService / SessionService / HeatmapService]
    end
    subgraph DOM["04-Domain-Layer"]
        I[Interfaces Repository]
        MU[Música: IntervalFormula, Heatmap, NoteResolver]
        ENT[Entidades: Song, Session, ...]
    end
    subgraph INFRA["05-Infrastructure-Layer"]
        REP[Repositorios MySql]
        PDO[PdoFactory / MySqlRepository]
    end

    R --> A
    M --> R
    A --> S
    S --> I
    S --> MU
    I --> REP
    REP --> PDO
    PDO --> DB[(06-Data-Model)]
```

## Flujo de una petición

```mermaid
sequenceDiagram
    participant FE as Frontend (08)
    participant COR as CorsMiddleware
    participant RT as RoutingMiddleware
    participant AC as Action
    participant SV as Service
    participant RP as Repository
    participant DB as MySQL

    FE->>COR: HTTP request
    COR->>RT: headers CORS añadidos (short-circuit OPTIONS)
    RT->>AC: resuelve ruta → Action (args como atributos del request)
    AC->>SV: delega lógica de negocio
    SV->>RP: usa interfaz del dominio
    RP->>DB: PDO/MySQL
    DB-->>RP: filas
    RP-->>SV: entidades/arrays
    SV-->>AC: DTOs serializados
    AC-->>COR: JSON + status (201/200/...)
    COR-->>FE: respuesta
```

## Decisiones clave

- **slim-bridge**: las Actions reciben `request`/`response` por nombre y los placeholders de ruta (`{id}`) se inyectan como **atributos** del request (`$request->getAttribute('id')`).
- **Errores**: `JsonErrorHandler` convierte excepciones del dominio y de Slim (`HttpException`) a JSON con códigos correctos (404/405/400/500) y maneja preflight OPTIONS (200 + `Allow`).
- **CORS**: `CorsMiddleware` es el middleware más externo; responde preflight y añade headers a toda respuesta.
- **Sin framework frontend**: JS vanilla; el estado vive en `app.js` y el audio usa Web Audio API.
