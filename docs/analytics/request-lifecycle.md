# Analytics Request Lifecycle

## End-to-End Flow

This flow covers both ingestion and admin consumption paths.

```mermaid
sequenceDiagram
participant User
participant FE as FE Tracker
participant IngestAPI as /api/v1/analytics/events
participant Redis
participant Scheduler
participant Flush as analytics:flush
participant Mongo
participant MySQL
participant AdminUI as Admin Analytics Page
participant AdminAPI as /jav/admin/analytics/*
participant ES as Elasticsearch

User->>FE: view/download action
FE->>IngestAPI: POST analytics event
IngestAPI->>Redis: dedupe + increment counters
IngestAPI-->>FE: 202 accepted
Scheduler->>Flush: periodic flush
Flush->>Redis: read and clear hot counters
Flush->>Mongo: write totals and period rollups
Flush->>MySQL: sync jav.views/downloads
AdminUI->>AdminAPI: request overview/distribution/trends
AdminAPI->>MySQL: snapshot/quality/top lists
AdminAPI->>ES: segment aggregations
AdminAPI-->>AdminUI: JSON response for charts/tables
```

## Middleware, Validation, Boundaries

- Ingest middleware: `api` + `throttle:analytics`.
- Admin analytics middleware: `web` + `auth` + `role:admin`.
- Validation:
  - Ingest: `IngestAnalyticsEventRequest`
  - Admin analytics queries: `AnalyticsApiRequest`
- Transaction boundaries:
  - Ingest is counter-based and non-transactional.
  - Flush handles each counter key independently to limit blast radius.
- Error handling:
  - Validation: `422`
  - Unauthorized/forbidden: `401/403`
  - Rate limiting: `429`
  - Backend dependency issue: `503`
  - Unexpected failure: `500`
