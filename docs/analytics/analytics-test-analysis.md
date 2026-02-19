# Analytics Test & Coverage Analysis (BE & FE)

Analysis from **Senior Software Architect**, **Senior Business Analyst**, **Senior Backend Developer**, **Senior Frontend Developer**, **Senior QA**, and **Senior QC** perspectives: missing test cases, uncovered edge cases, and current coverage estimates.

---

## 1. Senior Software Architect

### Missing test cases (architecture / cross-cutting)

| Area | Gap | Recommendation |
|------|-----|----------------|
| **Contract at boundary** | No explicit test that FE payload shape matches BE `IngestAnalyticsEventRequest` (field names, types, optional vs required). | Add a contract test (e.g. shared JSON schema or FE test that builds payload and asserts against same rules as BE). |
| **Idempotency** | Ingest is tested for duplicate `event_id`; no test that same request body sent twice with same `event_id` returns 202 both times and only counts once. | Add feature test: POST same payload twice, assert 202 both times, assert Redis/Mongo count once. |
| **Cross-domain** | Only `jav` domain is supported. No test that explicitly rejects or documents behavior for future domains. | Add test: invalid domain returns 422; document that new domains require enum + config. |
| **Flush atomicity** | Flush is tested for “no double count” when key is renamed; no test for partial failure (e.g. Mongo write fails after Redis rename). | Add test or scenario: Redis key consumed, one bucket fails → ensure key is not re-processed and errors are logged/countable. |
| **Auth vs anonymous** | Controller passes `Auth::id()` to ingest; no test for authenticated vs unauthenticated (e.g. `user_id` in payload or downstream). | Add test: POST as guest → 202 and ingest; POST as user → 202 and ingest with `user_id` if used. |

### Edge cases not covered

- **Rate limiter key** (e.g. by IP vs by user): only “rate limit returns 429” is tested; key strategy (IP vs user) and boundary (e.g. same user different IP) not asserted.
- **Clock skew**: `occurred_at` in the past/future; validation accepts any `date`; no test for “far future” or “far past” if business rules exist.
- **Redis prefix with colon** in config: `analytics.redis_prefix` with value containing `:` could change key parsing; not tested.

---

## 2. Senior Business Analyst

### Missing test cases (business rules / acceptance)

| Rule / Acceptance | Gap | Recommendation |
|-------------------|-----|----------------|
| **View = 1 count** | No explicit BA-style test: “When user opens movie page, exactly one view is recorded (or deduped).” | Already partly covered by Playwright `ui-view-tracking.spec.ts`; add acceptance test or doc that maps “user views movie” → one event (or dedupe). |
| **Download = server-side** | Download is tracked in `JavAnalyticsTrackerService::trackDownload`; no test that calling this results in ingest and correct counters. | Add unit/feature test: `trackDownload($jav)` → ingest called with action=download, entity_id=jav.uuid; optional: Redis/Mongo increment. |
| **Admin dashboard data** | Admin Analytics page shows totals, top viewed, top downloaded, quality, etc. No automated test that “given DB state, API returns correct numbers.” | Add feature test for snapshot/overview API (e.g. AnalyticsReadService or Admin controller) with known fixtures; assert counts and shape. |
| **Dedupe window** | Business rule: “same event_id not counted again within 48h”. Tested in unit; no high-level test that 48h is the actual TTL. | Add test or doc: assert dedupe key TTL = 172800 seconds (48h). |
| **Value bounds** | BE validates value 1–100. No test that value 1 and 100 both succeed and that 0 and 101 fail. | Endpoint test has value 0 and 101; add explicit test for value 1 and 100 accepted. |

### Edge cases not covered

- **Empty catalog**: Admin analytics with zero movies/actors — no test that overview/snapshot returns empty arrays and no errors.
- **Concurrent ingest** for same entity: multiple requests with different `event_id` for same entity_id; all should be counted (no lost updates). Not explicitly tested.

---

## 3. Senior Backend Developer

### Missing test cases (BE code paths)

| Component | Gap | Recommendation |
|-----------|-----|----------------|
| **AnalyticsEventController** | No direct unit test; covered only via feature test. | Optional: unit test with mocked `AnalyticsIngestService`, assert `ingest()` called with validated data and `Auth::id()`. |
| **IngestAnalyticsEventRequest** | Validation is covered by `AnalyticsIngestEndpointTest::invalidPayloadProvider`. Missing: **missing entity_id**, **entity_id empty string**, **entity_id length > 255**, **event_id length > 255**. | Add to provider: `'missing entity_id'`, `'entity_id empty'` (e.g. `''`), `'entity_id too long'` (256 chars), `'event_id too long'`. |
| **AnalyticsIngestService** | Well covered. Missing: `userId` passed to ingest is not persisted in current design; if it were, no test. | N/A unless you add user_id to storage. |
| **AnalyticsFlushService** | Good coverage. Missing: **parse error for date** in daily field (e.g. `view:invalid-date`) — code has try/catch and returns early; not tested. **MySQL sync when Jav not found** (entity_id not in `jav` table) — no test. | Add test: malformed date in counter field → no crash, no write. Add test: flush for movie entity_id that has no Jav row → Mongo updated, MySQL unchanged. |
| **JavAnalyticsTrackerService** | **Not tested.** | Add test: `trackDownload($jav)` calls ingest with domain=jav, entity_type=movie, entity_id=jav.uuid, action=download. |
| **FlushAnalyticsCountersJob** | Tested. Optional: job failure/retry and error count. | Add test: when flush throws, job fails or records errors (depending on design). |
| **AnalyticsParityService / Commands** | Parity and report commands have tests. Optional: parity when Mongo and MySQL intentionally differ. | Already present in integration tests. |
| **JAV Admin AnalyticsController (API)** | Admin endpoints (distribution, association, trends, actorInsights, etc.) — no dedicated test. | Add feature tests: valid request → 200 and shape; missing required param (e.g. genre, actor_uuid) → 422; Elasticsearch down → 503 if applicable. |

### Edge cases not covered

- **Redis connection lost** during ingest: endpoint test has Redis throw → 500; good. No test for Redis timeout or partial write.
- **Mongo / MySQL unavailable** during flush: flush test requires Mongo/Redis; no test that flush returns errors and does not lose Redis key (key is already renamed/deleted in current design).
- **Large payload** (e.g. very long event_id/entity_id): validation max 255; no test for 255 vs 256.

---

## 4. Senior Frontend Developer

### Missing test cases (FE)

| Component | Gap | Recommendation |
|-----------|-----|----------------|
| **AnalyticsService** | **Covered:** track success, dedupe, TTL expiry, cache eviction, overflow. **Missing:** `track('download', ...)` (allowed action); **reject** invalid action/entityType/entityId/domain; **options**: `dedupe: false` (always send); `userId` in payload; `occurredAt`/`eventId` passed through; **storage failure** (setItem throws) → still mark in memory; **hasTracked** when storage getItem throws. | Add tests: track with action `download`; track with invalid action/entityType/empty entityId/wrong domain → false; track with dedupe false twice → two POSTs; optional userId/occurredAt/eventId in payload; storage setItem throws → no exception, in-memory mark; hasTracked when getItem throws → return false. |
| **Singleton** | **Covered:** first track, dedupe, API failure. **Missing:** reset between tests (already has t.after). | Consider isolating tests so singleton state does not leak (or keep current approach and document). |
| **Show.vue** | **Contract test** (AnalyticsServiceContract.test.js): imports and calls `analyticsService.track('view', 'movie', props.jav?.uuid)` in onMounted with try/catch. **Missing:** when `props.jav` is null/undefined, track receives undefined entityId → service returns false; no component unit test. | Add test: track called with undefined entity_id (e.g. jav missing) → no crash, service returns false. Optionally add Vue component test (e.g. Vitest) for Show.vue that mocks analyticsService and asserts one call on mount. |
| **Admin Analytics.vue** | No unit/component tests. Page fetches data and renders charts/tables. | Add tests: with given props, key sections render; optional: mock API and assert request params. |

### Edge cases not covered

- **No sessionStorage** (e.g. private mode): service uses `storage = null`; hasTracked/markTracked only in-memory; dedupe still works in same tab. Not explicitly tested.
- **crypto.randomUUID unavailable**: fallback to `fallbackRandomId()`; not tested (mock globalThis.crypto).
- **Network retry**: service does not retry on POST failure; test only asserts return false. No test for “no retry”.

---

## 5. Senior QA

### Missing test cases (E2E / integration)

| Scenario | Gap | Recommendation |
|----------|-----|----------------|
| **E2E view flow** | Playwright: first view emits one event; duplicate view same session deduped; Redis/Mongo updated. **Missing:** second movie view in same session → second event; different sessions (new context) → both counted. | Add: view movie A then movie B → two events; view same movie in two different browser contexts → two events (or document session boundary). |
| **E2E download flow** | No E2E test that “user clicks download → server records download event”. | Add Playwright (or API) test: trigger download → assert download event in Redis or API. |
| **Rate limit E2E** | Endpoint test has rate limit 429. No E2E that many rapid views from same IP get 429 and no crash. | Optional: Playwright or script that fires many POSTs and asserts 429. |
| **Admin analytics E2E** | No test that Admin Analytics page loads and shows at least one chart/section without error. | Add: login as admin → open Analytics → expect no 5xx, key sections visible. |
| **Flush schedule** | No test that scheduled job actually runs (e.g. cron or scheduler) and that after flush, Mongo/MySQL reflect recent ingest. | Optional: test that flush command runs and data appears in Mongo/MySQL. |

### Edge cases not covered

- **Concurrent users** (many tabs/users): not tested; dedupe is per event_id, so safe.
- **Clock skew (client)**: FE sends `occurred_at` from client; no test that server accepts it and uses it for daily bucket (or document server authority).

---

## 6. Senior QC (Quality Control / Compliance)

### Missing test cases (correctness & evidence)

| Area | Gap | Recommendation |
|----------|-----|----------------|
| **Evidence commands** | `analytics:report:verify` and generate have tests. **Missing:** test that generated report is deterministic (same inputs → same artifact) for audit. | Add test: run generate twice with same data → same checksums or structure. |
| **Parity as regression** | Parity check is tested. **Missing:** test that when MySQL and Mongo are intentionally out of sync, parity check fails with clear output. | Add test: set Mongo total different from MySQL → parity fails. |
| **Schema versioning** | Evidence includes schema_version. **Missing:** test that old schema version is rejected or handled. | Add test: artifact with older schema_version → verify fails or warns. |
| **Data retention** | No test that dedupe TTL (48h) or retention of rollups is as per policy. | Document and optionally assert TTL and retention in tests. |

### Edge cases not covered

- **Audit trail**: who (user_id) did what (action) at what time (occurred_at) — if required for compliance, add tests that user_id is stored and queryable.

---

## 7. Current coverage estimate (BE / FE)

### Backend (PHP)

- **No PHP coverage report** was run in this repo (no `phpunit.xml` coverage config, and `vendor/bin/phpunit` was not run). The following is an **estimate** based on existing test files and code paths.

| Layer | Files / area | Estimated coverage | Notes |
|-------|----------------------|--------------------|--------|
| **Ingest** | `AnalyticsEventController`, `IngestAnalyticsEventRequest`, `AnalyticsIngestService` | **~85–90%** | Controller covered via feature test; request via validation provider (missing entity_id cases); service unit tests cover main paths and dedupe. |
| **Flush** | `AnalyticsFlushService`, `FlushAnalyticsCountersJob` | **~80–85%** | Flush key parsing, totals/daily/weekly/monthly/yearly, MySQL sync, malformed key, unsupported action covered; date parse failure and “Jav not found” not tested. |
| **Enums** | `AnalyticsAction`, `AnalyticsDomain`, `AnalyticsEntityType` | **~95%+** | Values and from/tryFrom tested. |
| **Parity / report** | `AnalyticsParityService`, report/verify commands | **~75–80%** | Integration and command tests present; edge cases for evidence/schema optional. |
| **JAV** | `JavAnalyticsTrackerService`, Admin `AnalyticsController` (API), `AnalyticsReadService` | **~40–50%** | ReadService has tests; JavAnalyticsTrackerService has **no** tests; Admin API endpoints not tested. |

**Overall BE (analytics-related code only): ~70–75%** (by lines/branches, approximate).

### Frontend (JS)

- **No JS coverage tool** is configured (no Jest/Vitest with coverage in `package.json`). Tests use Node `node:test`.

| Layer | Files | Estimated coverage | Notes |
|-------|--------|--------------------|--------|
| **analyticsService.js** | `AnalyticsService` class, default export | **~75–80%** | track success, dedupe, TTL, cache eviction tested; invalid args, download action, options (userId, dedupe false, occurredAt), storage exception paths not tested. |
| **Contract / singleton** | Contract and singleton tests | **~90%** of contract | Contract tests are file/string checks; singleton tests cover happy path and API failure. |
| **Vue (Analytics, Show)** | `Show.vue`, `Analytics.vue` | **~0%** (no component tests) | Only contract test that Show.vue calls track; no Vue Test Utils or Vitest component tests. |

**Overall FE (analytics-related JS only): ~65–70%** (approximate).

### How to get real coverage numbers

- **BE:** Add coverage to PHPUnit (e.g. `phpunit.xml` with `coverage-html` or `coverage-clover`), run:
  ```bash
  php vendor/bin/phpunit --coverage-html build/coverage
  ```
  Then open the report and filter by `Core`/`JAV` analytics namespaces.
- **FE:** Add Vitest (or Jest) with `coverage: true` and run tests for `analyticsService.js` and optionally `Show.vue`/`Analytics.vue` to get line/branch coverage.

---

## 8. Priority summary

| Priority | Action |
|----------|--------|
| **High** | Add validation tests for **missing entity_id**, **entity_id empty string**, **entity_id/event_id too long** in `AnalyticsIngestEndpointTest`. |
| **High** | Add tests for **JavAnalyticsTrackerService::trackDownload** (backend). |
| **High** | Add FE tests for **invalid track() args** (action, entityType, entityId, domain), **dedupe: false**, and **storage exception** in hasTracked/markTracked. |
| **Medium** | Add test: flush when **Jav row missing** for entity_id (MySQL unchanged). Add test: **malformed date** in flush counter field. |
| **Medium** | Add **Admin Analytics API** feature tests (distribution, actorInsights, etc.) for 200 and 422. |
| **Medium** | Add **Analytics.vue** and **Show.vue** component or integration tests (e.g. Vitest + Vue Test Utils). |
| **Low** | Enable **PHP and JS coverage** and add to CI; document target (e.g. 80% for analytics). |
| **Low** | Contract test for FE payload vs BE validation; E2E for download flow and admin Analytics page load. |

---

*Document generated from codebase review. Run coverage locally/CI to get exact percentages.*

---

## 9. Applied tests (implementation summary)

The following tests were added in response to this analysis:

- **AnalyticsIngestEndpointTest**: missing entity_id, entity_id empty, entity_id/event_id too long; value 1 and 100 accepted; idempotency (same payload twice → 202 both, count once); guest and authenticated both receive 202.
- **JavAnalyticsTrackerServiceTest** (new): `trackDownload` calls ingest with domain=jav, entity_type=movie, entity_id=jav.uuid, action=download.
- **AnalyticsFlushServiceTest**: malformed date in daily field does not crash; flush for movie entity_id with no Jav row updates Mongo only, not MySQL.
- **AnalyticsIngestServiceTest**: dedupe TTL is 48 hours (172800 seconds).
- **JAV Admin Api AnalyticsControllerTest** (new): distribution/actorInsights/association require genre/actor_uuid/segment_value (422 when missing/empty); admin endpoints require authentication (401 when guest).
- **AnalyticsService.test.js**: track('download'); reject invalid action/entityType/entityId/domain; dedupe:false (two POSTs); userId/occurredAt/eventId in payload; storage setItem throws → in-memory dedupe; hasTracked when getItem throws → false; uuidFactory fallback.
- **AnalyticsServiceContract.test.js**: FE payload shape contract (event_id, domain, entity_type, entity_id, action, value, occurred_at; optional user_id).
