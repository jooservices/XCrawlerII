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

- `GET /jav/admin/analytics/overview-data`
- `GET /jav/admin/analytics/distribution-data`
- `GET /jav/admin/analytics/association-data`
- `GET /jav/admin/analytics/trends-data`
- `POST /jav/admin/analytics/predict`
- `GET /jav/admin/analytics/actor-insights`
- `GET /jav/admin/analytics/quality-data`
- `GET /jav/admin/analytics/suggest`

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
