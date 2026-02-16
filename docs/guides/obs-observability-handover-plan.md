# OBS Observability Handover Plan (Core-Centric, Parallel-Ready)

## 1) Purpose

This document defines a complete implementation plan for integrating XCrawlerII with the OBS platform for operational observability, while preserving existing JAV Elasticsearch analytics responsibilities.

The plan is written so multiple developers/AI agents can execute in parallel with low coordination overhead.

---

## 2) Scope, Outcomes, and Boundaries

### In Scope

- Ship operational logs from XCrawlerII to OBS.
- Standardize event schema for logs, queue/job telemetry, and crawler reliability signals.
- Build actionable operational metrics for queue tuning and capacity planning.
- Support horizontal scaling decisions (workers/server count, server count).
- Keep tests fully offline (no real OBS requests in tests).

### Out of Scope

- Replacing JAV Elasticsearch product analytics.
- Migrating historical analytics indices.
- Building a full BI/reporting layer in OBS.

### Non-Overlap Contract (Hard Rule)

- OBS = operational telemetry (system health, reliability, queue/worker behavior).
- JAV Elasticsearch = business/product analytics (content/user/performance KPIs).

---

## 3) Why This Design

1. **Reliability first**: direct HTTP logging in request flow can fail/slow user paths.
2. **Scalability**: queue/event shipping supports burst loads and retries.
3. **Ownership clarity**: Core owns observability infrastructure; feature modules only emit structured events.
4. **Parallel delivery**: clear interfaces allow independent implementation of client, mapping, queue, and tests.
5. **Anti-blocking crawling strategy**: distributed server scaling with per-target rate controls avoids flood behavior from single-host over-concurrency.

---

## 4) Target Architecture

## 4.1 High-Level Flow

1. App emits structured operational event.
2. Event is normalized/redacted by Core mapper.
3. Event is dispatched to queue (`obs-telemetry` queue).
4. OBS sender job performs HTTP `POST /logs` with `x-api-key`.
5. Failures retry with backoff and jitter.
6. Persistent failure writes fallback local log + optional dead-letter marker.

## 4.2 Deployment Roles

- **API/ingestion app servers**: web + queue dispatch.
- **Worker servers**: consume crawl queues and OBS sender queues.
- **Optional dedicated OBS sender workers**: isolate telemetry shipping from crawl execution.

## 4.3 Core Ownership

Core module owns:

- OBS client interface + implementation.
- Event schema, mapper, redaction policy.
- Queue job for async shipping.
- Logging channel wiring.
- Operational alert/event taxonomy.

Feature modules (JAV, others) only:

- emit events via Core façade/service.
- do not call OBS API directly.

---

## 5) Configuration Contract

Add in `.env.example` and resolve via `config/services.php` (or `core/observability` config file if preferred):

- `OBS_ENABLED=true|false`
- `OBS_BASE_URL=https://obs.example.com`
- `OBS_API_KEY=...`
- `OBS_TIMEOUT_SECONDS=2`
- `OBS_RETRY_TIMES=3`
- `OBS_RETRY_SLEEP_MS=150`
- `OBS_QUEUE_ENABLED=true`
- `OBS_QUEUE_NAME=obs-telemetry`
- `OBS_SERVICE_NAME=xcrawlerii`
- `OBS_LOG_LEVEL_MIN=info`
- `OBS_REDACT_KEYS=password,token,authorization,cookie,api_key`

Notes:

- XCrawlerII uses `OBS_API_KEY`; outbound header to OBS is `x-api-key`.
- OBS payload `env` must be sourced from Laravel `APP_ENV` (single source of truth).
- Keep local file logging active in all environments via `LOG_STACK=single,obs` for fallback.
- Local development should use `OBS_ENABLED=false` by default (no OBS connectivity required).
- Keep `OBS_ENABLED=false` for initial deploy, then progressive enablement.

Failure-handling policy (production safe):

- Non-retryable OBS statuses (default: `400,401,403,413,422`) are dropped to local fallback logs without retry loops.
- Transient statuses/errors (`429`, `5xx`, network errors) are retried by queue job policy.
- Oversized payloads are automatically truncated (`context` reduced to metadata marker) before submit.
- Optional response contract validation via `OBS_REQUIRED_RESPONSE_KEY` protects against silent API response drift.
- Fallback local logs include machine-readable fields: `metric_key=obs_delivery_failure_total`, `obs_failure_type`, `obs_status_code`.

Implementation snippet (recommended):

```php
'obs' => [
   'enabled' => (bool) env('OBS_ENABLED', false),
   'base_url' => env('OBS_BASE_URL'),
   'api_key' => env('OBS_API_KEY'),
   'env' => env('APP_ENV', 'production'),
   // ...
],
```

---

## 6) Event Taxonomy (What to Emit)

## 6.1 Core Event Types

1. `app.log` (generic operational application log)
2. `http.request.completed`
3. `http.request.failed`
4. `queue.job.started`
5. `queue.job.completed`
6. `queue.job.failed`
7. `queue.rate_limit_exceeded`
8. `crawler.target.block_signal`
9. `crawler.target.cooldown_applied`
10. `dependency.call.completed`
11. `dependency.call.failed`
12. `deployment.marker`
13. `worker.health.snapshot`
14. `queue.depth.snapshot`

Implemented in current codebase:

- `queue.snapshot` (includes queue depth where available and jobs/sec).
- `crawler.target.block_signal` (derived from `403/429/503` failure signatures).
- `crawler.target.cooldown_applied` (cooldown recommendation event).
- `dependency.health` via command `php artisan obs:dependencies-health`.

Optional scheduler (disabled by default):

- Enable with `OBS_DEPENDENCY_HEALTH_SCHEDULE_ENABLED=true`.
- Configure cron with `OBS_DEPENDENCY_HEALTH_SCHEDULE_CRON` (default `*/5 * * * *`).
- Requires Laravel scheduler runner in production (`php artisan schedule:run` via system cron).
- Scheduled dependency health probe is guarded with `withoutOverlapping()` and `onOneServer()`.

## 6.2 Event Priorities

- `P0`: queue/job failures on critical jobs, severe block signals, dependency outage.
- `P1`: latency regressions, retries spike, lag increase.
- `P2`: normal completions and diagnostic info.

## 6.3 Required Common Fields

- `service` (e.g., `xcrawlerii`)
- `env` (`development|staging|production`)
- `level` (`DEBUG|INFO|WARN|ERROR`)
- `message`
- `timestamp` (UTC ISO8601)
- `traceId` (if available)
- `context` (object)
- `tags` (array)
- `eventType` (one of taxonomy values)

## 6.4 Queue/Job Context Fields

- `job_uuid`, `job_name`, `queue`, `connection`, `attempt`
- `duration_ms`, `timeout_ms_observed`
- `site`, `source`, `url`
- `worker_host`, `server_id`, `region`
- `status` (`running|success|failed|warning|critical`)

## 6.5 Block/Flood Detection Fields

- `target_host`
- `http_status` (`403|429|503|...`)
- `block_signal_type` (`captcha|rate_limit|forbidden|connection_reset|timeout`)
- `burst_window_seconds`
- `requests_in_window`
- `cooldown_seconds`
- `proxy_pool_id` / `egress_ip_group`

---

## 7) Metric Model (Derived from Events)

These are the primary metrics to drive queue/worker/server tuning.

## 7.1 Throughput & Latency

- `jobs_started_per_sec`
- `jobs_completed_per_sec`
- `jobs_failed_per_sec`
- `job_duration_ms_p50/p95/p99`

## 7.2 Reliability

- `job_success_rate`
- `job_failure_rate`
- `retry_rate`
- `timeout_rate`
- `dead_letter_growth_rate`

## 7.3 Queue Health

- `queue_depth`
- `queue_lag_seconds`
- `oldest_job_age_seconds`

## 7.4 Worker Efficiency

- `processed_jobs_per_worker_per_min`
- `worker_busy_percent`
- `worker_error_rate`

## 7.5 Anti-Block Safety

- `block_signal_rate_by_target`
- `429_rate_by_target`
- `403_rate_by_target`
- `cooldown_apply_rate`

## 7.6 Capacity & Scaling

- `throughput_per_server`
- `throughput_per_worker`
- `effective_parallelism`

---

## 8) Capacity Planning Formulas (Practical)

Let:

- `R_target` = desired req/sec per target group.
- `T_worker` = observed safe req/sec per worker.
- `U` = target utilization (0.6–0.75 recommended).
- `W_max_server` = max safe workers per server.

Then:

- `Workers Needed = ceil(R_target / (T_worker * U))`
- `Servers Needed = ceil(Workers Needed / W_max_server)`

Sizing guidance:

- Do not maximize workers on one server when block/flood risk exists.
- Prefer more servers with lower per-server concurrency.
- Apply per-target and per-egress pool limits first, then scale out.

---

## 9) Skeleton (Suggested Files / Contracts)

> Adjust paths if your team prefers `app/` global instead of module-local placement.

### 9.1 Core Contracts

- `Modules/Core/app/Observability/Contracts/ObservabilityClientInterface.php`
- `Modules/Core/app/Observability/Contracts/TelemetryEmitterInterface.php`

Methods:

- `sendLog(array $payload): void`
- `emit(string $eventType, array $context = [], string $level = 'info', ?string $message = null): void`

### 9.2 Core Implementations

- `Modules/Core/app/Observability/ObsHttpClient.php`
- `Modules/Core/app/Observability/TelemetryEmitter.php`
- `Modules/Core/app/Observability/ObsPayloadMapper.php`
- `Modules/Core/app/Observability/RedactionService.php`

### 9.3 Queue Job

- `Modules/Core/app/Jobs/SendObsTelemetryJob.php`

Responsibilities:

- idempotent send attempt
- retry with jitter
- fallback local warning log on permanent failure

### 9.4 Event Integration

- Reuse existing queue listeners in `Modules/Core/app/Listeners/Queue/*`.
- Extend `JobTelemetryService` output to also emit normalized OBS events.

### 9.5 Config

- Update `config/services.php` with `obs` section.
- Optional: `Modules/Core/config/observability.php` for module-level behavior knobs.
- Update `.env.example` with all OBS variables.

### 9.6 Logging Channel

- Update `config/logging.php`:
  - new `obs` custom/monolog channel.
  - include in `stack` channel order.
  - preserve `daily` fallback for resilience.

### 9.7 Tests

- `Modules/Core/tests/Unit/Observability/ObsPayloadMapperTest.php`
- `Modules/Core/tests/Unit/Observability/RedactionServiceTest.php`
- `Modules/Core/tests/Unit/Observability/ObsHttpClientTest.php` (with `Http::fake()`)
- `Modules/Core/tests/Feature/Observability/SendObsTelemetryJobTest.php`
- `Modules/Core/tests/Feature/Observability/QueueTelemetryToObsTest.php`

---

## 10) Implementation Phases and Parallel Workstreams

## Phase A: Foundation (1–2 days)

Tasks:

1. Add OBS config + environment variables.
2. Add Core contracts and stubs.
3. Register bindings in `CoreServiceProvider`.

Parallelizable:

- Dev A: config + providers.
- Dev B: interfaces + baseline implementations.

Exit criteria:

- app boots with OBS disabled/enabled without runtime errors.

## Phase B: Event Pipeline (2–4 days)

Tasks:

1. Implement mapper and redaction rules.
2. Implement async queue job sender.
3. Hook queue telemetry events to emitter.

Parallelizable:

- Dev A: mapper/redaction.
- Dev B: queue job/retry policy.
- Dev C: queue listener integration.

Exit criteria:

- queue events produce OBS-ready payloads and enqueue send jobs.

## Phase C: Logging Channel Integration (1–2 days)

Tasks:

1. Add `obs` logging channel.
2. Wire stack channel with fallback behavior.
3. Add deployment marker event emission.

Parallelizable:

- Dev A: logging config.
- Dev B: deployment marker command/hook.

Exit criteria:

- operational app logs route through OBS pipeline with fallback preserved.

## Phase D: Metrics + Alerts (2–3 days)

Tasks:

1. Define dashboards from event fields.
2. Define alert thresholds for queue lag/failure/block rates.
3. Add runbook links in alert descriptions.

Parallelizable:

- Dev A: metrics dictionary.
- Dev B: alert policy + runbooks.

Exit criteria:

- on-call can answer “scale workers or servers?” from dashboards.

## Phase E: Capacity Tuning & Scale Policy (2–5 days)

Tasks:

1. Establish per-target safe rate budgets.
2. Set per-target concurrency + cooldown policies.
3. Define autoscaling guardrails (scale out only if block signal is healthy).

Parallelizable:

- Dev A: target policy configs.
- Dev B: autoscaling + operational playbooks.

Exit criteria:

- predictable scale behavior without block-signal explosion.

---

## 11) Test Strategy (No Real OBS Requests)

## 11.1 Principles

- Never call real OBS in unit/feature tests.
- Use `Http::fake()` for all outbound requests.
- Use `Queue::fake()` where queue dispatch assertion is needed.

## 11.2 Core Test Cases

1. **Config Load**
   - reads `OBS_API_KEY`, base URL, timeout values correctly.
2. **Payload Mapping**
   - all required fields present; invalid keys redacted.
3. **Header Contract**
   - outbound includes `x-api-key` exactly.
4. **Success Path**
   - fake `202`; sender marks success.
5. **Failure Path**
   - fake timeout/500; retries occur; fallback log written.
6. **Queue Integration**
   - queue telemetry emits send job with expected payload.
7. **Boundary Guard**
   - product analytics events are not sent to OBS pipeline.
8. **Idempotency/Retry Safety**
   - duplicate job executions do not create uncontrolled duplicates.

## 11.3 Suggested CI Commands

- `composer test -- --filter=Observability`
- `composer test -- --filter=JobTelemetry`

Optional full gate:

- `composer quality:full`

---

## 12) Alert and Runbook Blueprint

## 12.1 Alert Rules (Starter)

1. Queue lag > 120s for 5 minutes.
2. Failed jobs/sec > baseline + threshold for 3 windows.
3. p95 `duration_ms` for critical queue > threshold.
4. `429` or `403` rate spike by target > threshold.
5. Dead-letter growth continuously positive for 10 minutes.

## 12.2 Runbook Decision Tree

When lag is high:

- if block rate is low + CPU high => scale out workers/servers.
- if block rate is high => reduce per-target rate, increase cooldown, do not blindly scale workers.
- if failure/timeout concentrated by target => isolate queue and apply target-specific throttling.

---

## 13) Rollout Strategy

1. Deploy code with `OBS_ENABLED=false`.
2. Enable in staging; run burn-in tests under synthetic queue load.
3. Enable subset of event types in production (queue telemetry first).
4. Enable broader app logs after stability.
5. Tune thresholds weekly for 2–4 weeks.

Rollback:

- set `OBS_ENABLED=false` and continue with local log fallback.

---

## 14) Task Board Template (Handover-Friendly)

Use this template for parallel teams:

- **Track A (Config/Infra)**: env/config/provider bindings.
- **Track B (Client/Mapper)**: OBS client, schema mapper, redaction.
- **Track C (Queue/Event)**: sender job, listener wiring, retries/backoff.
- **Track D (Tests)**: unit + feature with fakes.
- **Track E (Dashboards/Alerts)**: metrics dictionary, alerts, runbooks.
- **Track F (Capacity Ops)**: per-target budgets, autoscaling guardrails.

Each task must define:

- owner
- dependencies
- acceptance criteria
- rollback steps
- test evidence (screenshots/log output/test results)

---

## 15) Risks and Mitigations

1. **Risk**: OBS outage affects app behavior.
   - **Mitigation**: async queue, retry/backoff, fallback local logging.
2. **Risk**: event flood increases telemetry cost.
   - **Mitigation**: sampling for low-value info logs, minimum level gates.
3. **Risk**: secret leakage in context payloads.
   - **Mitigation**: strict redaction allowlist/denylist and tests.
4. **Risk**: confusion with JAV ES analytics.
   - **Mitigation**: hard taxonomy boundary and mapper guard rails.
5. **Risk**: over-scaling single server causes target blocks.
   - **Mitigation**: horizontal scaling, per-target rate caps, cooldown/circuit breaker.

---

## 16) Definition of Done

Done when all are true:

1. OBS integration is enabled in Core and documented.
2. Queue/job telemetry events are visible and queryable in OBS.
3. Dashboards answer worker/server sizing decisions.
4. Block/flood signal alerts are active with runbooks.
5. No real OBS calls in automated tests; all test suites pass with fakes.
6. Non-overlap with JAV Elasticsearch analytics is validated by tests and mapper rules.

---

## 17) Immediate Next Actions (Recommended Order)

1. Implement config + interfaces + service provider bindings.
2. Implement payload mapper + redaction + sender job.
3. Hook existing `JobTelemetryService`/queue listeners to emitter.
4. Add focused observability tests with `Http::fake()` and `Queue::fake()`.
5. Prepare first dashboard/alerts from queue telemetry and block signals.
