# Request Lifecycle

## End-to-End Path (Authenticated API Example)

Example route: `GET /jav/api/dashboard/items`

1. Request enters Laravel kernel.
2. Middleware chain executes:
   - `web` bootstraps session/cookies/csrf context.
   - `auth` ensures authenticated identity.
3. Controller method validates/filter inputs.
4. Repository/service builds query and applies business rules.
5. Data fetched from DB and/or search engine.
6. Domain decoration layer enriches items (liked, watchlist, rating state).
7. Response transformed to JSON contract.
8. Errors mapped to HTTP status with validation/error payloads.

## Lifecycle Diagram

```mermaid
sequenceDiagram
participant Client
participant Router
participant Middleware
participant Controller
participant ServiceRepo as Service/Repository
participant DB
participant Search as Elasticsearch
participant Response

Client->>Router: GET /jav/api/dashboard/items
Router->>Middleware: route middleware pipeline
Middleware->>Controller: authorized request
Controller->>ServiceRepo: validate + request filters
ServiceRepo->>DB: relational reads
ServiceRepo->>Search: search/aggregation queries (as needed)
DB-->>ServiceRepo: records
Search-->>ServiceRepo: search hits
ServiceRepo-->>Controller: normalized result DTO
Controller->>Response: JSON transform
Response-->>Client: 200 / 4xx / 5xx
```

## Transaction and Error Boundaries

- Read-only endpoints avoid DB transactions unless explicitly needed.
- Write endpoints (`store`, `update`, `destroy`) should keep business updates atomic.
- Validation errors return 422 with field-level messages.
- Authorization errors return 401/403.
- Not-found resources return 404.
- Unexpected failures are logged and surfaced as safe 500 responses.

## Queue-Driven Lifecycle (Sync)

```mermaid
sequenceDiagram
participant AdminUI
participant SyncAPI
participant Queue
participant Worker
participant Telemetry
participant Catalog

AdminUI->>SyncAPI: dispatch sync
SyncAPI->>Queue: enqueue jobs
Queue-->>Worker: deliver job
Worker->>Telemetry: started
Worker->>Catalog: parse + persist
Worker->>Telemetry: completed/failed
```

This flow isolates long-running source operations from HTTP response time.
