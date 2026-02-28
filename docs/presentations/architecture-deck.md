# Slide: Vision & scope

- **XCrawler:** Modular Laravel/Vue platform for crawling and advanced search by metadata.
- **Initial focus:** JAV-related site metadata ingestion and retrieval.
- **Core flow:** Discover → Fetch → Parse → Normalize → Expose search APIs.
- **Goal:** Extensible to new content domains and target types without rewriting the core.
- **Non-goals:** Monolithic app; feature modules depending on each other; shared logic outside Core.

---

# Slide: Tech stack

- **Backend:** Laravel 12, PHP 8.5.
- **Frontend:** Vue 3, Inertia.js, PrimeVue, FontAwesome, Vite.
- **Script style:** Composition API, `<script setup lang="ts">` only.
- **Modules:** `nwidart/laravel-modules`; Core = only shared module.
- **Data:** MariaDB, MongoDB, Redis, Elasticsearch.
- **Boundary packages:** `jooservices/dto`, `jooservices/client`.

---

# Slide: Module boundaries

- **Core:** Only shared module; domain-agnostic (contracts, base classes, shared FE assets).
- **Feature modules:** Depend **only** on Core; never on each other.
- **Inter-feature:** Via contracts/interfaces in Core only.
- **Rule IDs:** MOD-001 (Core scope), MOD-002 (feature → Core only), MOD-003 (contracts), MOD-004 (Core → no feature).
- **Doc:** [docs/architecture/01-module-boundaries.md](../architecture/01-module-boundaries.md).

---

# Slide: Backend layering

- **Golden path:** Controller → FormRequest → Service → Repository → Model.
- **Thin controllers:** No DB, no business logic, no transactions.
- **Transactions:** Service layer only.
- **1 Model ↔ 1 Repository;** authorization in Controller/FormRequest; Services receive authorized context.
- **Rule IDs:** BE-REQ-001–BE-REQ-007.
- **Doc:** [docs/architecture/02-backend-layering.md](../architecture/02-backend-layering.md).

---

# Slide: Data stores

- **MariaDB:** Relational, transactional (users, config, feature data).
- **MongoDB:** Documents, logs, high-volume / append-heavy data.
- **Redis:** Cache, sessions, queues, rate limiting.
- **Elasticsearch:** Search index over normalized metadata; structured + range queries.
- **Doc:** [docs/reference/00-system-overview.md](../reference/00-system-overview.md).

---

# Slide: Model standards (TABLE/COLLECTION, Mongo folder)

- **Naming:** Model = singular of table/collection (e.g. `client_logs` → `ClientLog`, `xcrawler_logs` → `XCrawlerLog`).
- **Constants:** Every model: `const TABLE` or `const COLLECTION`; `$table = self::TABLE` or `self::COLLECTION`.
- **MongoDB:** Extend `\Modules\Core\app\Models\MongoDb`; live in `Modules/<Module>/app/Models/MongoDb/`.
- **Fillable:** Explicit `$fillable`; no `$guarded = []`; timestamps enabled, not in fillable.
- **Rule IDs:** DATA-MOD-001–DATA-MOD-005.
- **Doc:** [docs/architecture/03-data-model-standards.md](../architecture/03-data-model-standards.md).

---

# Slide: Frontend layering

- **Page:** Orchestration/layout only; uses Composables + Services.
- **Component:** Presentational (props, events); no API calls.
- **Composable:** UI logic/state; may call Services.
- **Service:** API client wrapper only (calls backend via shared httpClient).
- **Shared assets:** Only in Core (MasterLayout, httpClient, shared components).
- **Rule IDs:** FE-ARCH-001–FE-ARCH-006.
- **Doc:** [docs/architecture/04-frontend-standards.md](../architecture/04-frontend-standards.md).

---

# Slide: Observability / logging (high-level)

- **Logs:** Structured logging; crawler/client logs can go to MongoDB.
- **Correlation/trace:** Use correlation_id / trace_id in logs and queues where applicable.
- **No new rules here:** Implementation follows project logging config and Core utilities.
- **Reference:** [docs/reference/00-system-overview.md](../reference/00-system-overview.md) (data stores).

---

# Slide: Testing strategy

- **Feature tests:** Full flow; no mocking of internal Services/Repositories (TEST-001).
- **Unit tests:** Target correct class; mock only external boundaries (TEST-002).
- **Data:** Factory + Faker always (TEST-003).
- **Base class:** Module tests extend `Modules\Core\Tests\TestCase` (TEST-004).
- **Coverage:** Happy, unhappy, weird, security/exploit, edge (TEST-005); no placeholders (TEST-007).
- **Env:** Use `.env.testing` / `APP_ENV=testing` (TEST-006).
- **Doc:** [docs/architecture/05-testing-standards.md](../architecture/05-testing-standards.md).

---

# Slide: CI & git hooks enforcement (high-level)

- **CI:** Run tests (PHPUnit with test env), lint, static analysis (e.g. PHPStan), frontend build/lint as configured.
- **Git hooks:** Pre-commit / pre-push can run pint, phpstan, tests; commit message format (e.g. Conventional Commits) per project.
- **Quality gates:** Align with [docs/architecture/06-code-review-checklist.md](../architecture/06-code-review-checklist.md); blockers must pass before merge.
- **Details:** See project `.github/workflows/`, `.githooks/`, and architecture 12–14 if present.

---

# Slide: Common violations (with Rule IDs)

- **MOD-002 / MOD-003:** Feature importing another feature; fix: use Core contract.
- **BE-REQ-001 / BE-REQ-002:** Controller with `DB::` or `Model::query()`; fix: move to Service/Repository.
- **BE-REQ-003:** Transaction in Controller/Repository; fix: move to Service.
- **DATA-MOD-002 / DATA-MOD-003:** Model without TABLE/COLLECTION constant; Mongo model in wrong folder or not extending Core base; fix: add constant, move path, extend `MongoDb`.
- **FE-ARCH-005:** Shared layout/httpClient in feature module; fix: move to Core.
- **TEST-001:** Feature test mocking internal Service; fix: use real service (test DB).
- **TEST-004:** Module test extending `Tests\TestCase`; fix: extend `Modules\Core\Tests\TestCase`.

---

# Slide: Where to read more

- **Rules (the law):** [docs/architecture/](../architecture/) — 00–06.
- **Explanations:** [docs/reference/](../reference/) — overview, lifecycle, module map.
- **Diagrams:** [docs/reference/diagrams/](../reference/diagrams/) — module-deps, request-flow, data-flows.
- **Decisions:** [docs/adr/](../adr/) — e.g. 0001-docs-structure.
- **Runbooks:** [docs/playbooks/](../playbooks/) — e.g. 00-pr-onboarding-playbook.
- **Index:** [docs/README.md](../README.md) — “Which doc do I read?”
