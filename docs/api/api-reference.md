# API Reference

## Authentication

- User APIs require authenticated session (`auth` middleware).
- Admin APIs require both `auth` and `role:admin`.

## Core Endpoints

### Dashboard Items
- Method: `GET`
- Path: `/jav/api/dashboard/items`
- Purpose: return paginated dashboard items with filters/sort.

Example request:

```http
GET /jav/api/dashboard/items?sort=created_at&direction=desc
```

Example response:

```json
{
  "data": [],
  "current_page": 1,
  "last_page": 1
}
```

### Search Suggestions
- Method: `GET`
- Path: `/jav/api/search/suggest`
- Purpose: mixed suggestions for movie/actor/tag by query.

Example request:

```http
GET /jav/api/search/suggest?q=alpha&limit=8
```

Example response:

```json
{
  "query": "alpha",
  "suggestions": [
    {"type": "movie", "label": "ABP-123 Alpha", "href": "/jav/movies/..."}
  ]
}
```

### Watchlist
- `POST /jav/api/watchlist`
- `PUT /jav/api/watchlist/{watchlist}`
- `DELETE /jav/api/watchlist/{watchlist}`
- `GET /jav/api/watchlist/check/{javId}`

### Ratings
- `POST /jav/api/ratings`
- `PUT /jav/api/ratings/{rating}`
- `DELETE /jav/api/ratings/{rating}`
- `GET /jav/api/ratings/check/{javId}`

### Notifications
- `GET /jav/api/notifications`
- `POST /jav/api/notifications/{notification}/read`
- `POST /jav/api/notifications/read-all`

## Admin Endpoints (Selected)

- `GET /admin/job-telemetry/summary-data`
- `GET /jav/admin/analytics/*`
- `POST /jav/admin/provider-sync/dispatch`

## Error Catalog

- `401 Unauthorized`: unauthenticated access.
- `403 Forbidden`: insufficient role/permission.
- `404 Not Found`: missing resource.
- `422 Unprocessable Entity`: validation failure.
- `500 Internal Server Error`: unexpected server-side failure.
