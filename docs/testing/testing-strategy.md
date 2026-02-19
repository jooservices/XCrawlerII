# Testing Strategy

## Test Layers

- **Unit tests:** Enums (`AnalyticsAction`, `AnalyticsDomain`, `AnalyticsEntityType`), services (`AnalyticsIngestService`, `AnalyticsFlushService`), and helper services. Mock Redis and Mongo/MySQL where appropriate.
- **Feature tests:** Ingest endpoint (validation, throttle, 202), admin analytics endpoints (auth, role, success/error), flush command/job, parity and report commands.
- **Integration tests:** Parity checks, operational simulations, production-like analytics flow (ingest → flush → Mongo/MySQL).
- **Frontend unit tests:** `Modules/Core/resources/js/Services/__tests__/AnalyticsService.test.js` and related (singleton, contract, allowed actions/entity types).

## Unit Test Examples (Given/When/Then)

### Analytics dedupe (ingest)
- **Given** an event with `event_id` already present in Redis (`anl:evt:{id}`).
- **When** `AnalyticsIngestService::ingest()` is called again with the same event.
- **Then** no counter increments occur (method returns without HINCRBY).

### Flush rollups
- **Given** hot Redis counters for one movie (e.g. `anl:counters:jav:movie:{uuid}` with `view` and `view:YYYY-MM-DD`).
- **When** `AnalyticsFlushService::flush()` runs.
- **Then** Mongo documents exist for totals and daily (and weekly/monthly/yearly derived); MySQL `jav.views` (and `jav.downloads` if present) are updated for that UUID.

### Ingest validation
- **Given** a POST to `/api/v1/analytics/events` with invalid `action` (e.g. `invalid_action`).
- **When** the request is processed.
- **Then** response is 422 with validation errors.

## What to Mock

- **Redis** in unit tests for ingest and flush (or use a test Redis instance in feature tests).
- **MongoDB/MySQL** for isolated service tests; feature tests may use in-memory or test DB.
- **Elasticsearch** client for unit-level admin analytics logic.
- **Queue dispatch** side effects when testing controllers or jobs in isolation.
- **External provider/crawler** clients.

## Critical Integration / Feature Cases

- `POST /api/v1/analytics/events` accepts valid payload (202) and rejects invalid payload (422) and respects throttle (429).
- `analytics:flush` handles malformed Redis keys without aborting the whole batch (logs and skips).
- `analytics:parity-check` detects mismatches between MySQL and Mongo totals.
- Admin analytics endpoints return 401/403 when unauthenticated or non-admin; 503 when Elasticsearch is unavailable (where used).
- FE analytics service: allowed actions/entity types, dedupe behavior, no hardcoded endpoint in app code (contract tests).

## Execution Commands

```bash
composer test
php artisan test Modules/Core/tests/Feature/Analytics
php artisan test Modules/Core/tests/Unit/Services/AnalyticsIngestServiceTest.php
php artisan test Modules/Core/tests/Unit/Services/AnalyticsFlushServiceTest.php
npm run test:fe
```

For analytics code structure and request flow, see [Analytics code structure](../analytics/code-structure.md) and [Analytics request lifecycle](../analytics/request-lifecycle.md).
