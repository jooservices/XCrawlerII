# Testing Strategy

## Test Layers

- Unit tests: service, repository, model, and utility behavior.
- Feature tests: request validation, auth guards, endpoint contracts.
- Contract tests: JSON shape and critical route behavior.

## Unit Testing Guidance (Given/When/Then)

### Example 1: Search suggestions
- Given valid query and seeded data
- When client calls suggest endpoint/service
- Then response contains mixed typed suggestions and stable shape

### Example 2: Telemetry aggregation
- Given telemetry events in time window
- When summary service computes metrics
- Then p50/p95/failure rate and throughput are correct

## What to Mock

- External provider clients (`jooservices/client` wrappers).
- Network-bound crawler adapters.
- Queue dispatch side effects for unit scope.

## Integration Cases

- Auth and role middleware behavior.
- Dashboard filtered search with DB + index interaction.
- Watchlist/rating ownership constraints.
- Admin sync dispatch and telemetry page contracts.

## Test Data Requirements

- Factories for `Jav`, `Actor`, `Tag`, `User`, `Rating`, `Watchlist`.
- Seed representative combinations:
  - high/low popularity items
  - cross-tag overlap
  - actor profile completeness variance

## Execution Commands

```bash
composer test
composer quality:full
```

Use `quality:full` as pre-merge and pre-release gate.
