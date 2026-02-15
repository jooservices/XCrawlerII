# Implementation Guide

## Implementation Workflow (Zero Ambiguity)

1. Define route contract (web/API, auth, role middleware).
2. Add/adjust Request validation rules.
3. Implement service/repository behavior.
4. Expose response via controller (JSON or Inertia props).
5. Add or update tests:
   - unit tests for service/repository logic
   - feature tests for endpoint behavior and security
6. Run quality gate:
   - `composer format`
   - `composer quality:full`

## Feature Build Steps Example: New Admin Analytics Endpoint

1. Add endpoint in `Modules/JAV/routes/web.php` under admin auth/role group.
2. Add request validation in `Modules/JAV/app/Http/Requests`.
3. Add service method in `ActorAnalyticsService` or dedicated service.
4. Add controller action returning stable JSON contract.
5. Add feature tests for:
   - auth guard
   - valid payload
   - invalid payload
   - empty dataset behavior
6. Update docs in `docs/api/api-reference.md`.

## Coding Rules to Follow

- Keep request validation strict at boundaries.
- Keep controller thin; move domain logic to services/repositories.
- Preserve route naming conventions (`jav.*`, `jav.api.*`, `jav.vue.*`, `admin.*`).
- Prefer explicit return shapes for API contracts.

## Quality Gate Ordering

1. `composer format`
2. `composer quality`
3. `composer test`

Use `composer quality:full` before merge/release.
