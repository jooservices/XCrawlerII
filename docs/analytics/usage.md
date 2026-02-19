# Analytics Usage (FE / BE)

## Frontend Usage

Main FE tracker: `Modules/Core/resources/js/Services/analyticsService.js`

Typical usage:

```js
import analyticsService from '@core/Services/analyticsService';

await analyticsService.track('view', 'movie', movieUuid);
```

Behavior:
- Validates allowed actions/entity types.
- Uses session + memory dedupe to avoid repeated track calls.
- Posts to `/api/v1/analytics/events`.
- Returns boolean success/failure without breaking UX.

## Backend Usage

Main BE tracker: `Modules/JAV/app/Services/JavAnalyticsTrackerService.php`

Typical usage:
- Call `trackDownload($jav)` when download action occurs.
- Service emits normalized event through `AnalyticsIngestService`.

## Admin Analytics Usage

- Open `/jav/admin/analytics` for snapshot + advanced insights.
- Advanced endpoints are served from `Modules/JAV/app/Http/Controllers/Admin/Api/AnalyticsController.php`.
- Telemetry summary is available at `/admin/job-telemetry/summary-data`.

## CLI Usage

- Flush hot counters: `php artisan analytics:flush`
- Check parity: `php artisan analytics:parity-check --limit=100`
- Generate evidence: `php artisan analytics:report:generate --days=7 --limit=500`
- Verify evidence: `php artisan analytics:report:verify --dir=storage/logs/analytics/evidence --strict`
