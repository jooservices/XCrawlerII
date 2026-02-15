# Getting Started (Developer)

## Prerequisites

- PHP 8.5
- Composer 2.x
- Node.js 20+ and npm
- Redis
- MySQL/MariaDB
- Optional: MongoDB (telemetry), Elasticsearch (search)

## 5-Minute Local Run Target

```bash
composer setup
composer hooks:install
composer dev
```

This installs dependencies, initializes environment, runs migrations, and starts local services.

## Environment Variables

Minimum keys to verify:

- `APP_ENV`, `APP_URL`, `APP_KEY`
- `DB_*` (database connection)
- `REDIS_*` (queue/cache)
- `SCOUT_DRIVER`, `ELASTICSEARCH_*` (search)
- `JOB_TELEMETRY_*` (telemetry controls)

## Validation Commands

```bash
composer format
composer quality
composer test
```

## Expected Local Outcome

- App pages load at local server URL.
- Queue worker receives jobs.
- Dashboard queries return results.
- Admin telemetry page returns summary data.
