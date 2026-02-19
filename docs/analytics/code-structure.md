# Analytics Code Structure (FE / BE)

## Backend Structure

### Core analytics ingest and rollup

- Route: `Modules/Core/routes/api.php`
- Controller: `Modules/Core/app/Http/Controllers/Api/AnalyticsEventController.php`
- Validation: `Modules/Core/app/Http/Requests/IngestAnalyticsEventRequest.php`
- Ingest service: `Modules/Core/app/Services/AnalyticsIngestService.php`
- Flush service: `Modules/Core/app/Services/AnalyticsFlushService.php`
- Flush job: `Modules/Core/app/Jobs/FlushAnalyticsCountersJob.php`

### Core analytics operations

- Commands:
  - `Modules/Core/app/Console/FlushAnalyticsCommand.php`
  - `Modules/Core/app/Console/AnalyticsParityCheckCommand.php`
  - `Modules/Core/app/Console/AnalyticsReportGenerateCommand.php`
  - `Modules/Core/app/Console/AnalyticsReportVerifyCommand.php`
- Scheduling + rate limiter: `Modules/Core/app/Providers/CoreServiceProvider.php`
- Config: `config/analytics.php`

### JAV admin analytics

- Web/Admin routes: `Modules/JAV/routes/web.php`
- Admin page controller: `Modules/JAV/app/Http/Controllers/Admin/AnalyticsController.php`
- Admin API controller: `Modules/JAV/app/Http/Controllers/Admin/Api/AnalyticsController.php`
- Services:
  - `Modules/JAV/app/Services/AnalyticsReadService.php`
  - `Modules/JAV/app/Services/ActorAnalyticsService.php`
  - `Modules/JAV/app/Services/JobTelemetryAnalyticsService.php`
  - `Modules/JAV/app/Services/JavAnalyticsTrackerService.php`

## Frontend Structure

- Tracker service: `Modules/Core/resources/js/Services/analyticsService.js`
- Admin analytics page: `Modules/JAV/resources/js/Pages/Admin/Analytics.vue`
- Actor insights component: `Modules/JAV/resources/js/Components/ActorInsightsPanel.vue`
- Consumer example: `Modules/JAV/resources/js/Pages/Movies/Show.vue`

## Logic Flow by Layer

1. FE/BE producer emits normalized event.
2. API validates and increments Redis counters.
3. Scheduler flushes counters into Mongo rollups.
4. Flush syncs totals to MySQL movie counters.
5. Admin UI reads analytics via MySQL + Elasticsearch + telemetry queries.
