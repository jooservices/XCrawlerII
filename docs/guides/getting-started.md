# Getting Started

This guide gets a developer running the project locally quickly. Target: **run the project and basic analytics flow within a short setup window**.

## Prerequisites

- PHP `^8.5`
- Composer 2.x
- Node.js 22 LTS (or compatible modern Node)
- MySQL/MariaDB
- Redis
- MongoDB (required for analytics rollups and telemetry)
- Elasticsearch (required for advanced admin analytics; optional for core catalog and snapshot)

## Run Locally

```bash
composer setup
composer hooks:install
composer dev
```

This installs dependencies, sets up hooks, and starts the dev server (backend + frontend build/watch as configured).

## Minimum Environment Variables

Copy `.env.example` to `.env` and set at least:

| Variable | Purpose |
|----------|---------|
| `APP_URL`, `APP_KEY` | Application URL and encryption key |
| `DB_CONNECTION`, `DB_*` | MySQL connection |
| `REDIS_CLIENT`, `REDIS_HOST`, `REDIS_PORT` | Redis (used by analytics ingest and cache) |
| `MONGODB_*` | MongoDB connection (analytics rollups, telemetry) |
| `SCOUT_DRIVER`, `ELASTICSEARCH_HOST` | Search / admin analytics (can be disabled for minimal setup) |
| `ANALYTICS_REDIS_PREFIX` | Redis key prefix for counters (default `anl:counters`) |
| `ANALYTICS_FLUSH_INTERVAL` | Minutes between scheduled flush (default `1`) |
| `ANALYTICS_RATE_LIMIT` | Per-minute throttle for ingest API (default `60`) |

See `config/analytics.php` and `.env.example` for the full list (e.g. evidence options).

## Quick Verification

```bash
php artisan route:list --path="analytics"
php artisan analytics:flush
composer test
```

Expected:

- **Route list:** `POST api/v1/analytics/events` appears under the analytics prefix.
- **Flush:** Command prints "Flushed X keys, Y errors." (0 keys is fine on a fresh install.)
- **Tests:** Suite runs without critical failures.

For detailed analytics setup and usage, see [Analytics documentation](../analytics/README.md) and [Getting started (environment)](../analytics/usage.md#cli-usage).
