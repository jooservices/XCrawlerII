# XCrawlerII

XCrawlerII is a modular Laravel platform for crawling, normalizing, indexing, and exploring JAV metadata with user-facing discovery features and admin operations tooling.

## Key Features

- Multi-source crawling and normalization pipeline.
- Search and discovery via dashboard, actors, tags, and movie pages.
- User features: watchlist, ratings, favorites, history, notifications, preferences.
- Admin features: sync orchestration, analytics insights, queue telemetry.
- Queue monitoring with Horizon and Mongo-based telemetry events.

## Tech Stack

- Backend: Laravel 12, modular architecture (`Modules/*`).
- Frontend: Inertia + Vue.
- Queue: Redis + Horizon.
- Search: Scout + Elasticsearch.
- Telemetry snapshots/events: MongoDB.

## Quick Start

```bash
composer setup
composer hooks:install
composer dev
```

## Quality and Testing

```bash
composer format        # auto-fix (Pint, then PHPCBF)
composer quality       # lint + phpstan + phpmd
composer quality:full  # quality + tests
```

Git hooks are configured to enforce:

- `pre-commit`: lint checks.
- `pre-push`: full tests.

## Documentation

- [Documentation Index](docs/README.md)
- [User Guide](docs/user-guide.md)
- [Developer Guide](docs/developer-guide.md)
- [Architecture](docs/architecture.md)
- [Release Checklist](docs/release-checklist.md)

## License

This project is distributed under the MIT License unless stated otherwise.
