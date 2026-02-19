# Implementation Guide

## Feature Workflow

1. Define route contract and middleware.
2. Add/update request validation.
3. Implement service logic in module service class.
4. Keep controllers thin and return stable contracts.
5. Add unit + feature tests.
6. Update docs for API/lifecycle changes.

## Example: Add a New Analytics Action

1. Extend enum in `Modules/Core/app/Enums/AnalyticsAction.php`.
2. Update validation rules in `Modules/Core/app/Http/Requests/IngestAnalyticsEventRequest.php`.
3. Extend flush support list in `Modules/Core/app/Services/AnalyticsFlushService.php`.
4. Update FE allowed actions in `Modules/Core/resources/js/Services/analyticsService.js`.
5. Add/adjust tests in `Modules/Core/tests/Feature/Analytics` and `Modules/Core/tests/Unit/Services`.
6. Update `docs/analytics/*` and `docs/api/api-reference.md`.

## Implementation Steps for Admin Analytics Endpoint

1. Add endpoint in `Modules/JAV/routes/web.php` under admin middleware.
2. Extend `AnalyticsApiRequest` for payload constraints.
3. Add service method in `Modules/JAV/app/Services/ActorAnalyticsService.php`.
4. Implement controller action in `Modules/JAV/app/Http/Controllers/Admin/Api/AnalyticsController.php`.
5. Use endpoint from `Modules/JAV/resources/js/Pages/Admin/Analytics.vue`.
6. Add feature tests for auth, validation, success, and error paths.

## Quality Gate

```bash
composer format
composer quality
composer test
```
