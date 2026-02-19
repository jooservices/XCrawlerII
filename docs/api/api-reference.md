# API Reference

## Authentication

- User web APIs: `auth` middleware.
- Admin analytics APIs: `auth` + `role:admin`.
- Public analytics ingest: route-level throttle, no session required.

## Analytics Ingest API

### `POST /api/v1/analytics/events`

Purpose: ingest one event into Redis hot counters.

Middleware:
- `api`
- `throttle:analytics`

Request body example:

```json
{
  "event_id": "4f6c1a5d-2a69-4ce6-a436-8fc2a4c1135b",
  "domain": "jav",
  "entity_type": "movie",
  "entity_id": "f6f7163c-4c8c-4ce8-bfe5-7dbd7574d273",
  "action": "view",
  "value": 1,
  "occurred_at": "2026-02-19T10:34:01Z"
}
```

Success response:

```json
{
  "status": "accepted"
}
```

Status: `202 Accepted`

## Admin Analytics APIs (Selected)

All require `auth` and `role:admin`. Query/body parameters are validated by `AnalyticsApiRequest`. See [Analytics usage](../analytics/usage.md#advanced-admin-api-charts-segments-insights) for usage context.

| Method | Path | Purpose |
|--------|------|---------|
| GET | `/jav/admin/analytics/overview-data` | Overview payload; optional `size` (default 8). |
| GET | `/jav/admin/analytics/distribution-data` | Distribution by dimension (e.g. `age_bucket`) for a genre; requires `genre`; optional `dimension`, `size`. |
| GET | `/jav/admin/analytics/association-data` | Association rules for a segment; requires `segment_value`; optional `segment_type`, `size`, `min_support`. |
| GET | `/jav/admin/analytics/trends-data` | Trends by dimension/genre/interval; optional `dimension`, `genre`, `interval` (week|month), `size`. |
| POST | `/jav/admin/analytics/predict` | Genre prediction; body params per `ActorAnalyticsService::predictGenres`; optional `size`. |
| GET | `/jav/admin/analytics/actor-insights` | Insights for one actor; requires `actor_uuid`; optional `size`. Returns 404 if actor not found. |
| GET | `/jav/admin/analytics/quality-data` | Quality metrics (no required params). |
| GET | `/jav/admin/analytics/suggest` | Suggestions; optional `type` (actor|genre|birthplace|blood_type), `q`, `size`. |

**Example request (overview):**

```http
GET /jav/admin/analytics/overview-data?size=8
Cookie: <session>
```

**Example response (200):** JSON object; structure depends on endpoint (overview returns aggregated counts/segments for dashboard charts).

**Error responses:** `401` (unauthenticated), `403` (not admin), `422` (validation, e.g. missing `genre` or `actor_uuid`), `503` (e.g. Elasticsearch unavailable), `500` (unexpected).

## Telemetry API (Admin)

- `GET /admin/job-telemetry/summary-data`

## Core User APIs (Selected)

- `GET /jav/api/dashboard/items`
- `GET /jav/api/search/suggest`
- `POST /jav/api/watchlist`
- `POST /jav/api/ratings`

## Error Code Catalog

- `401 Unauthorized`: session/auth missing.
- `403 Forbidden`: role/permission denied.
- `404 Not Found`: entity or route resource not found.
- `422 Unprocessable Entity`: request validation failed.
- `429 Too Many Requests`: analytics throttle exceeded.
- `500 Internal Server Error`: unexpected server-side error.
- `503 Service Unavailable`: analytics backend dependency unavailable.
