<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework. You can also check out [Laravel Learn](https://laravel.com/learn), where you will be guided through building a modern Laravel application.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Redberry](https://redberry.international/laravel-development)**
- **[Active Logic](https://activelogic.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Queue Telemetry (Mongo)

This project logs queue lifecycle telemetry for every job to MongoDB:

- `started` when a job begins processing.
- `completed` with `status=success|failed` when it finishes.
- `rate_limit_exceeded` when per-site jobs/second crosses configured thresholds.

### Why this exists

- Measure execution time for all jobs (not only failures).
- Detect per-site flood risk (`jobs/sec`) before target sites block or throttle crawlers.
- Tune retries, concurrency and timeout based on data (`p95`, fail rate, timeout trend).

### Data model (`job_events` collection)

Core fields:

- `event_type`: `started`, `completed`, `rate_limit_exceeded`
- `status`: `running`, `success`, `failed`, `warning`, `critical`
- `timestamp`, `second_bucket`, `expire_at` (TTL)
- `job_uuid`, `job_name`, `queue`, `connection`, `attempt`, `worker_host`
- `site`, `source`, `url`
- `started_at`, `finished_at`, `duration_ms`

Failure-only fields:

- `error_class`, `error_code`, `error_message_short`
- `timeout_ms_observed` (parsed from timeout exception messages when available)

Rate alert fields:

- `jobs_per_second`, `warning_threshold`, `critical_threshold`

### Configuration

Environment variables:

- `JOB_TELEMETRY_ENABLED`
- `JOB_TELEMETRY_TIMER_TTL_SECONDS`
- `JOB_TELEMETRY_RETENTION_DAYS`
- `JOB_TELEMETRY_AUTO_CREATE_INDEXES`
- `JOB_TELEMETRY_RATE_ENABLED`
- `JOB_TELEMETRY_RATE_WARNING_PER_SECOND`
- `JOB_TELEMETRY_RATE_CRITICAL_PER_SECOND`

Module config (override in `Modules/Core/config/job_telemetry.php`):

- `site_thresholds`: per-site warning/critical override.
- `site_map_by_job`: fallback map from job class to site/source.
- `site_fields`, `url_fields`: fields extracted from serialized job payload.

### Indexes

Indexes are auto-created on first telemetry write when `JOB_TELEMETRY_AUTO_CREATE_INDEXES=true`:

- TTL on `expire_at`.
- Query indexes on `timestamp`, `status+timestamp`, `site+timestamp`, `job_name+timestamp`, `second_bucket+site`, `job_uuid`.

### Operational examples

- Top slow jobs (last 1 hour): filter `event_type=completed,status=success` and sort by `duration_ms` desc.
- Timeout trend by site: filter `status=failed,error_class~ConnectException` and group by `site` + minute.
- Flood detection: filter `event_type=rate_limit_exceeded` and inspect `jobs_per_second` vs threshold.

### Deployment notes

- Feature is fail-open: if Mongo is unavailable, job execution continues and telemetry errors are written to application logs.
- Use UTC consistently for dashboards and alert windows.
- Keep raw retention modest (e.g. 14–30 days), then aggregate for long-term reporting.
