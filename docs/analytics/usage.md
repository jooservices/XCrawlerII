# Analytics Usage (Frontend / Backend / Admin / CLI)

This document describes **how to use** the analytics system from the frontend (FE), backend (BE), admin UI, and command line. Each audience can follow the steps below without reading the full codebase.

---

## Frontend (FE) Usage

### Where the tracker lives

- **File:** `Modules/Core/resources/js/Services/analyticsService.js`
- **Export:** A singleton instance; import it and call `track(...)`.

### What you can track

- **Actions:** `view`, `download`
- **Entity types:** `movie`, `actor`, `tag`
- **Domain:** Only `jav` is accepted (other domains are rejected by the service).

### Basic usage (recommended)

```js
import analyticsService from '@core/Services/analyticsService';

// Track a movie view (e.g. on movie detail page mount)
await analyticsService.track('view', 'movie', movieUuid);

// Track with optional options
await analyticsService.track('view', 'movie', movieUuid, {
    userId: page.props.auth?.user?.id,  // optional
    dedupe: true,                       // default: true – skip if already tracked this session
    eventId: 'optional-custom-uuid',    // optional; usually auto-generated
    occurredAt: new Date().toISOString(), // optional
});
```

### Behavior (what the FE service does)

1. **Validation**  
   If `action` is not `view` or `download`, or `entityType` is not `movie`/`actor`/`tag`, or `entityId` is missing, the call returns `false` without sending a request.

2. **Session + in-memory dedupe**  
   If `dedupe` is true (default), the service checks a key like `anl:track:v1:view:movie:{entityId}` in session storage and in memory. If already tracked, it returns `false` and does not POST.

3. **Request**  
   It POSTs to `/api/v1/analytics/events` with a payload like:
   - `event_id` (UUID, from option or auto-generated)
   - `domain`: `jav`
   - `entity_type`, `entity_id`, `action`
   - `value`: 1
   - `occurred_at`: ISO 8601 string

4. **Response handling**  
   On success (e.g. 202), it marks the key as tracked (session + memory) and returns `true`. On any error (network, 4xx/5xx), it returns `false` and does not break the page (fail silently for UX).

### Example: Movie detail page (real usage in codebase)

In `Modules/JAV/resources/js/Pages/Movies/Show.vue`:

```js
import analyticsService from '@core/Services/analyticsService';

onMounted(async () => {
    try {
        await analyticsService.track('view', 'movie', props.jav?.uuid);
    } catch {
        // Swallow analytics errors so the page still works
    }
});
```

### Important for FE developers

- **Do not** call `/api/v1/analytics/events` directly from pages; always use `analyticsService` so validation and dedupe are consistent.
- **Do not** block the UI on analytics; the service returns a boolean and does not throw on failure.
- Allowed actions and entity types are fixed in the service; adding new ones requires backend and config changes (see [Implementation guide](../guides/implementation-guide.md)).

---

## Backend (BE) Usage

### When to track on the server

Use the backend tracker when the action happens **only on the server** (e.g. download completion). For page views, the frontend tracker is used.

### Main BE tracker service

- **Class:** `Modules\JAV\Services\JavAnalyticsTrackerService`
- **Method:** `trackDownload(Jav $jav)` — emits one download event for that movie.

### How it works

1. You call `trackDownload($jav)` (or in the future, similar methods for other actions).
2. The service builds a normalized event array with:
   - `event_id`: new UUID
   - `domain`: `jav`
   - `entity_type`: `movie`
   - `entity_id`: `$jav->uuid`
   - `action`: `download`
   - `value`: 1
   - `occurred_at`: current time (ISO 8601)
3. It calls `AnalyticsIngestService::ingest($event)`.
4. Ingest writes to Redis (dedupe + counter increments); no HTTP call.

### Example: Where download is tracked

When a download action is completed, the controller or job that performs it should call:

```php
use Modules\JAV\Services\JavAnalyticsTrackerService;

// In a controller or job
$this->javAnalyticsTrackerService->trackDownload($jav);
```

The actual wiring is in the place that handles the download action (e.g. download controller or job); the analytics call is fire-and-forget (no return value used for UX).

### Adding new server-side actions

To add a new action (e.g. `share`):

1. Add the action to `Modules\Core\Enums\AnalyticsAction`.
2. Extend `IngestAnalyticsEventRequest` validation and `AnalyticsFlushService::SUPPORTED_ACTIONS` (and Mongo model fields if needed).
3. Add a method on `JavAnalyticsTrackerService` (or equivalent) that builds the event and calls `$this->ingestService->ingest(...)`.
4. Call that method from the code path where the action occurs.

---

## Admin Analytics Usage

### Opening the analytics page

- **URL:** `/jav/admin/analytics` (or named route `admin.analytics`).
- **Access:** Requires authenticated user with admin role (middleware: `web`, `auth`, `role:admin`).

### What the page shows (snapshot data)

The main page is a Vue app (`Modules/JAV/resources/js/Pages/Admin/Analytics.vue`) that receives server-side props from `Modules\JAV\Http\Controllers\Admin\AnalyticsController`:

- **Totals:** Counts of jav, actors, tags.
- **Today created:** Counts created today for jav, actors, tags.
- **Daily created:** Time series (labels + values) for catalog growth over the selected day range.
- **Provider stats:** Per-source counts and window counts.
- **Top viewed / top downloaded / top rated:** Lists from MySQL (views/downloads synced from analytics).
- **Quality:** Missing actors/tags/images/dates, orphan counts, average actors/tags per jav.
- **Sync health:** Pending jobs, failed jobs in last 24h.

All snapshot data is built by `Modules\JAV\Services\AnalyticsReadService::getSnapshot($days)` from MySQL (and schema checks); no Elasticsearch is required for this snapshot.

### Advanced admin API (charts, segments, insights)

The Vue page can call additional endpoints under `/jav/admin/analytics/` (all require admin):

| Endpoint | Purpose |
|----------|--------|
| `GET /jav/admin/analytics/overview-data` | Overview payload (e.g. size-based). |
| `GET /jav/admin/analytics/distribution-data` | Distribution by dimension (e.g. age_bucket) for a genre. |
| `GET /jav/admin/analytics/association-data` | Association rules for a segment. |
| `GET /jav/admin/analytics/trends-data` | Trends by dimension/genre/interval. |
| `POST /jav/admin/analytics/predict` | Genre prediction from payload. |
| `GET /jav/admin/analytics/actor-insights` | Insights for a given actor UUID. |
| `GET /jav/admin/analytics/quality-data` | Quality metrics. |
| `GET /jav/admin/analytics/suggest` | Suggestions (type + query). |

These are implemented in `Modules\JAV\Http\Controllers\Admin\Api\AnalyticsController` and use `ActorAnalyticsService` (and optionally Elasticsearch). Request/response shapes and query params are documented in [API reference](../api/api-reference.md).

### Job telemetry (operational)

- **URL:** `/admin/job-telemetry/summary-data` (or the corresponding admin telemetry page).
- **Purpose:** Summary of queue/job lifecycle events (e.g. started, completed, rate_limit_exceeded) for operational monitoring.

---

## CLI Usage

These commands are run on the server or locally (e.g. in CI or cron).

### Flush hot counters

```bash
php artisan analytics:flush
```

- **What it does:** Reads all keys under the analytics Redis prefix, moves counts into Mongo (totals + daily/weekly/monthly/yearly), syncs `jav.views` and `jav.downloads` in MySQL, then deletes the Redis counter keys.
- **When:** Run manually for debugging, or rely on the scheduler (see `config/analytics.php`: `schedule_flush`, `flush_interval_minutes`).
- **Output:** "Flushed X keys, Y errors." Non-zero errors are also logged.

### Parity check (Mongo vs MySQL)

```bash
php artisan analytics:parity-check --limit=100
```

- **What it does:** Compares MySQL `jav.views`/`jav.downloads` with Mongo totals for the same entities. Reports mismatches (e.g. after a flush issue).
- **Use case:** Verify that flush and MySQL sync are correct.

### Generate evidence (artifacts)

```bash
php artisan analytics:report:generate --days=7 --limit=500
```

- **What it does:** Generates parity/evidence artifacts for the last N days (and optional rollback artifacts), optionally archived. Output directory is configurable via `config/analytics.php` (`evidence.output_dir`, etc.).
- **When:** Often scheduled daily (e.g. `analytics.evidence.schedule_daily` and `analytics.evidence.daily_at`).

### Verify evidence (integrity check)

```bash
php artisan analytics:report:verify --dir=storage/logs/analytics/evidence --strict
```

- **What it does:** Validates artifact schema and integrity in the given directory. Use for auditing or after generating evidence.

### Environment variables (CLI / scheduler)

Relevant options (see `config/analytics.php` and `.env.example`):

- `ANALYTICS_REDIS_PREFIX` – Redis key prefix for counters (default `anl:counters`).
- `ANALYTICS_FLUSH_INTERVAL` – Minutes between scheduled flush runs (default 1).
- `ANALYTICS_SCHEDULE_FLUSH` – Whether to schedule `analytics:flush` (default true).
- `ANALYTICS_RATE_LIMIT` – Per-minute throttle for ingest API (default 60).
- `ANALYTICS_EVIDENCE_*` – Evidence output dir, days, limit, schedule, etc.

---

## Quick reference

| Who | What to do |
|-----|------------|
| **FE developer** | Import `analyticsService` from `@core/Services/analyticsService`, call `track('view', 'movie', uuid)` (or download if tracked from FE). Do not POST to ingest API directly. |
| **BE developer** | Use `JavAnalyticsTrackerService::trackDownload($jav)` where download completes. Use `AnalyticsIngestService::ingest()` only from a dedicated tracker service. |
| **Admin user** | Open `/jav/admin/analytics` for snapshot and advanced charts; use telemetry page for queue health. |
| **Operator / DevOps** | Ensure scheduler runs `analytics:flush`; run `analytics:parity-check` and `analytics:report:verify` as needed. |

For code structure and request flow, see [Code structure](code-structure.md) and [Request lifecycle](request-lifecycle.md).
