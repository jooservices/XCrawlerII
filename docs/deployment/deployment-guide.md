# Deployment Guide

## Environments

- Development: local machine, relaxed observability settings.
- Staging: production-like config for validation.
- Production: full worker pools, telemetry retention policy, hardened secrets.

## CI/CD Pipeline Overview

1. Install dependencies.
2. Run quality gate (`composer quality`).
3. Run tests (`composer test`).
4. Build frontend assets.
5. Deploy app code.
6. Run migrations.
7. Restart/reload queue workers and Horizon.
8. Execute smoke checks.

## Release Steps

1. Tag and push release commit.
2. Deploy app and config.
3. Apply migrations.
4. Warm caches if used.
5. Start/reload workers.
6. Verify critical paths and admin health pages.

## Rollback Procedure

1. Revert to previous release artifact/tag.
2. Restart app and workers.
3. If needed, rollback migrations that are safe/reversible.
4. Verify dashboard, sync dispatch, and telemetry endpoints.

## Post-Release Verification

- Dashboard loads and search works.
- Sync dispatch endpoint responds.
- Queue telemetry records new events.
- Error logs contain no sustained critical spikes.
