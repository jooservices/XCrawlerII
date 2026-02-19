# Troubleshooting / FAQ

## Clarity Review Loop (3 Iterations)

### Iteration 1 Questions
- "Where do I start if I am new?"
- "Which doc explains business value vs implementation?"

Resolution:
- Added explicit reading order and scope in `docs/README.md`.

### Iteration 2 Questions
- "How does analytics data move from FE request to persistent storage?"
- "Which layer validates payload and handles rate limits?"

Resolution:
- Added full lifecycle docs in `docs/architecture/request-lifecycle.md` and `docs/analytics/request-lifecycle.md`.

### Iteration 3 Questions
- "How do FE and BE integrate analytics without duplicate events?"
- "Where are classes/routes to modify for analytics features?"

Resolution:
- Added FE/BE usage and structure docs in `docs/analytics/usage.md` and `docs/analytics/code-structure.md`.

No remaining unresolved questions.

## Common Pitfalls

1. **Analytics ingest returns `429`**  
   Throttle exceeded. Reduce event burst frequency or increase `ANALYTICS_RATE_LIMIT` in config (per minute). See `config/analytics.php` and route middleware `throttle:analytics`.

2. **Counters appear in Redis but not in Mongo**  
   Flush has not run or failed. Ensure the Laravel scheduler is running (`php artisan schedule:run` via cron) and that `ANALYTICS_SCHEDULE_FLUSH` is true. Run `php artisan analytics:flush` manually and check for errors in logs.

3. **Admin analytics page is empty or slow**  
   Snapshot data comes from MySQL (no Elasticsearch required). If the page loads but charts are empty, check that catalog and analytics data exist. For advanced endpoints (distribution, trends, actor-insights), Elasticsearch must be available; if it is down, those calls may return 503.

4. **`actor-insights` returns 404**  
   The given `actor_uuid` was not found. Ensure the actor exists in the current environment and that the UUID is correct.

5. **Telemetry charts show no points**  
   Validate that job telemetry events are being written to the telemetry store and that the selected time window includes those events.

6. **Duplicate views counted**  
   Frontend uses session + in-memory dedupe; backend uses `event_id` dedupe in Redis (48h TTL). Same `event_id` sent twice is only counted once. If duplicates persist, check that the FE is not sending different `event_id`s for the same logical event, or that BE is not calling ingest multiple times per action.

7. **MySQL `jav.views`/`jav.downloads` do not match Mongo totals**  
   Run `php artisan analytics:parity-check --limit=...` to list mismatches. Usually caused by flush timing (recent events not yet flushed) or a past flush error. Re-running flush and re-checking parity is the next step.

## Error-to-Action

| Code | Meaning | Action |
|------|---------|--------|
| 401 | Unauthenticated | Ensure user is logged in (admin endpoints). |
| 403 | Forbidden | Ensure user has admin role for admin analytics. |
| 422 | Validation failed | Fix request payload: required fields, enums (`domain`, `entity_type`, `action`), date format. See [API reference](../api/api-reference.md) and [IngestAnalyticsEventRequest](../../Modules/Core/app/Http/Requests/IngestAnalyticsEventRequest.php). |
| 429 | Throttle exceeded | Retry with backoff; or increase `ANALYTICS_RATE_LIMIT` if appropriate. |
| 503 | Dependency unavailable | Often Elasticsearch for admin advanced APIs; check service health and connectivity. |
| 500 | Unexpected server error | Inspect logs (`storage/logs`), fix root cause, retry. |

For full request lifecycle and middleware, see [Analytics request lifecycle](../analytics/request-lifecycle.md).
