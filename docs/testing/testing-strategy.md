# Testing Strategy

## Test Layers

- Unit tests: enums and services (`AnalyticsIngestService`, flush logic, helper services).
- Feature tests: ingest endpoint, admin analytics endpoints, middleware behavior.
- Integration tests: parity checks and operational simulations.
- Frontend unit tests: `Modules/Core/resources/js/Services/__tests__/*.test.js`.

## Unit Test Examples (Given/When/Then)

### Analytics dedupe
- Given an event with existing `event_id`
- When ingest is called again
- Then counters remain unchanged

### Flush rollups
- Given hot Redis counters for one movie
- When flush runs
- Then totals and period buckets are created in Mongo and MySQL counters sync

## What to Mock

- External provider/crawler clients.
- Elasticsearch client for unit-level analytics logic.
- Queue dispatch side effects for isolated unit tests.

## Critical Integration Cases

- `POST /api/v1/analytics/events` accepts valid payload and rejects invalid payload.
- `analytics:flush` handles malformed keys without aborting batch.
- `analytics:parity-check` detects mismatches.
- Admin analytics endpoints require admin role.

## Execution Commands

```bash
composer test
php artisan test Modules/Core/tests/Feature/Analytics
npm run test:fe
```
