# Analytics Overview

## What

XCrawlerII analytics is a hybrid system:
- Event ingest pipeline for action counters (`view`, `download`).
- Admin insights pipeline for actor/genre distributions, trends, and quality.
- Operational telemetry pipeline for queue performance and rate alerts.

## Why

- Product teams need measurable engagement and catalog quality.
- Operators need fast feedback on queue and sync health.
- Admins need segment analysis for decisions (content strategy, quality fixes).

## How It Was Developed

1. Defined strict event contract (`domain`, `entity_type`, `action`, `occurred_at`, `event_id`).
2. Implemented fast ingest in Redis with dedupe protection.
3. Added scheduled flush into Mongo rollups and MySQL parity sync.
4. Built admin analytics APIs and Vue dashboards for insights.
5. Added parity/report commands and test coverage for evidence.

## Business KPIs for Analytics Module

- Event ingest acceptance rate.
- Dedupe effectiveness (duplicate suppression).
- Flush reliability (error count per flush run).
- Parity mismatch count between Mongo and MySQL.
- Admin dashboard responsiveness and completeness.
