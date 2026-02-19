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

1. Analytics endpoint returns `429`
- Reduce event burst frequency or increase `ANALYTICS_RATE_LIMIT` carefully.

2. Counters appear in Redis but not Mongo
- Check scheduler/worker health and run `php artisan analytics:flush` manually.

3. Admin analytics page is empty
- Verify Elasticsearch and dataset availability.

4. `actor-insights` returns `404`
- Ensure `actor_uuid` exists in the current environment.

5. Telemetry charts show no points
- Validate `job_telemetry_events` ingestion and selected time window.

## Error-to-Action

- `422`: fix payload shape or value range.
- `429`: throttle exceeded; retry with backoff.
- `503`: dependency unavailable (often Elasticsearch); check service health.
- `500`: inspect logs and retry after root-cause fix.
