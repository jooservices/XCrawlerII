# Deployment Guide

## Environments

- `dev`: local iteration, relaxed debugging.
- `staging`: pre-release verification with production-like infra.
- `prod`: hardened config and controlled rollout.

## CI/CD Overview

1. Install PHP and Node dependencies.
2. Run quality/test gates.
3. Build frontend assets (`npm run build`).
4. Deploy code and run migrations.
5. Restart workers and verify scheduled commands.

## Required Services

- MySQL
- Redis
- MongoDB
- Elasticsearch
- Queue workers + scheduler

## Analytics Deployment Requirements

- **Scheduler:** Ensure the Laravel scheduler runs (e.g. cron: `* * * * * php artisan schedule:run`). Without it, Redis counters are never flushed to Mongo/MySQL.
- **Flush:** Confirm `analytics:flush` is scheduled in `CoreServiceProvider` (driven by `ANALYTICS_SCHEDULE_FLUSH` and `ANALYTICS_FLUSH_INTERVAL`).
- **Evidence:** Enable `analytics:report:generate` schedule if you need daily evidence artifacts (`ANALYTICS_EVIDENCE_SCHEDULE_DAILY`, etc.).
- **Throttle:** Set `ANALYTICS_RATE_LIMIT` appropriately for expected ingest traffic.

For full analytics flow and CLI, see [Analytics documentation](../analytics/README.md) and [Usage (CLI)](../analytics/usage.md#cli-usage).

## Environment Variables (Analytics)

- `ANALYTICS_REDIS_PREFIX`
- `ANALYTICS_FLUSH_INTERVAL`
- `ANALYTICS_RATE_LIMIT`
- `ANALYTICS_SCHEDULE_FLUSH`
- `ANALYTICS_EVIDENCE_SCHEDULE_DAILY`
- `ANALYTICS_EVIDENCE_DAILY_AT`
- `ANALYTICS_EVIDENCE_DAYS`
- `ANALYTICS_EVIDENCE_LIMIT`
- `ANALYTICS_EVIDENCE_OUTPUT_DIR`

## Rollback Procedure

1. Stop new deploy traffic.
2. Revert to previous release artifact.
3. Re-run config cache + route cache if used.
4. Restart workers.
5. Run `analytics:report:verify` for artifact integrity if analytics rollout was part of release.
