# Getting Started

## Prerequisites

- PHP `^8.5`
- Composer 2.x
- Node.js 22 LTS (or compatible modern Node)
- MySQL/MariaDB
- Redis
- MongoDB (required for analytics rollups/telemetry)
- Elasticsearch (required for advanced admin analytics)

## Run Locally

```bash
composer setup
composer hooks:install
composer dev
```

## Minimum Environment Variables

- `APP_URL`, `APP_KEY`
- `DB_*`
- `REDIS_*`
- `MONGODB_*`
- `SCOUT_DRIVER`, `ELASTICSEARCH_HOST`
- `ANALYTICS_REDIS_PREFIX`, `ANALYTICS_FLUSH_INTERVAL`, `ANALYTICS_RATE_LIMIT`

## Quick Verification

```bash
php artisan route:list --path="analytics"
php artisan analytics:flush
composer test
```

Expected:
- Analytics ingest endpoint exists.
- Flush command completes.
- Test suite runs without critical failures.
