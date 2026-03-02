# 01 - JAV Module: Copy and Refactor Playbook

This playbook describes how to **copy** the JAV module from XCrawlerII into the Cursor workspace and **refactor** it to follow the policies and rules in `./docs`. Use it as a checklist before and during the copy-refactor work.

**Terminology (see [03-jav-fetch-parse-flow](../reference/03-jav-fetch-parse-flow.md)):** **JAV** = module name. **jav** / **Jav** = one movie. Movies come from providers **onejav**, **141jav**, **ffjav**.

---

## Table of Contents

1. [What We Are Copying](#1-what-we-are-copying)
2. [What We Must Refactor (Policy Alignment)](#2-what-we-must-refactor-policy-alignment)
3. [Dependency and Environment Gaps](#3-dependency-and-environment-gaps)
4. [Phased Plan: What We Can Do](#4-phased-plan-what-we-can-do)
5. [Step-by-Step Refactor Checklist](#5-step-by-step-refactor-checklist)
6. [References](#6-references)

---

## 1. What We Are Copying

| Source | Destination |
|--------|-------------|
| `XCrawlerII/Modules/JAV/` | `Cursor/Modules/JAV/` |

**Contents to copy (conceptually):**

- **app/** — Services, Jobs, Events, Listeners, Models, Http (Controllers, Requests), Repositories, DTOs, Contracts, Support, Console, Providers, Enums, Facades
- **database/** — migrations, factories, seeders
- **resources/** — js (pages, components, composables, services, types), views, lang
- **routes/** — web.php, api (e.g. api_v1.php or per-module api files)
- **tests/** — Feature, Unit, FormRequest, Frontend (unit, e2e)
- **module.json**, **composer.json** (if module has its own)

**Do not copy:** Anything outside `Modules/JAV`; vendor or node_modules; env-specific or secrets.

---

## 2. What We Must Refactor (Policy Alignment)

These are the main areas where the existing JAV code (in XCrawlerII) may not yet match Cursor’s `./docs` rules. Refactor during or right after copy.

### 2.1 Backend Layering ([02-backend-layering](architecture/02-backend-layering.md))

| Rule | Requirement | Current JAV (XCrawlerII) | Refactor |
|------|-------------|---------------------------|----------|
| **BE-REQ-001** | Controller → FormRequest → Service → Repository → Model | Controllers call Services; JavManager (or MovieManager) must call repositories only. | Implement **MovieRepository**, **ActorRepository**, **TagRepository**, **MovieActorRepository**, **MovieTagRepository**. JavManager uses these five only (no `Movie::query()`, etc.). See [03 §3.4](../reference/03-jav-fetch-parse-flow.md#34-model-and-repository-skeleton-detail). |
| **BE-REQ-002** | No DB in Controller | Verify no `DB::`, `Model::query()`, `->where(`, `->create(` in any `*Controller.php`. | Grep and remove or move to Service/Repository. |
| **BE-REQ-004** | One Model per Repository | One repository per model. | MovieRepository ↔ Movie; ActorRepository ↔ Actor; TagRepository ↔ Tag; MovieActorRepository ↔ MovieActor; MovieTagRepository ↔ MovieTag. |
| **BE-REQ-006** | No hardcoded domain literals | Sources like `'onejav'`, `'141jav'`, `'ffjav'` may be string literals. | Introduce an Enum or Constants (e.g. `JavSource::Onejav`, `JavSource::OneFourOne`, `JavSource::Ffjav`) and use it everywhere. |
| **BE-REQ-007** | Jobs orchestrate Services; Listeners side-effects only | Jobs already call Services. MovieSubscriber calls JavManager (persistence). | Listener triggering “store” is acceptable if the event is the contract (ItemParsed → store). Ensure no business logic in Job beyond “call service”. |

### 2.2 Project Structure ([00-project-structure](architecture/00-project-structure.md))

| Rule | Requirement | Refactor |
|------|-------------|----------|
| **Module template** | `app/` has Constants, Enums, Http/Controllers, Http/Requests, Services, Repositories, Models, Policies, Jobs, Events, Listeners, **DTO** (or Dto), Providers | Use **DTO** (or match Core: Core uses `Dto`). Place DTOs under `Modules/JAV/app/DTO/` or `Dtos/` per project convention. |
| **Tests** | Feature module tests extend `Modules\Core\Tests\TestCase`. No local `tests/TestCase.php` unless exception-registered. | Ensure all JAV tests extend `Modules\Core\Tests\TestCase`. Remove or replace any local TestCase. |
| **00-STR-001** | Frontend tests under `tests/Frontend/{unit,e2e}`, not next to components. | Move any `*.spec.*` from `resources/js/` into `tests/Frontend/unit/` or `e2e/`. |
| **00-STR-004** | MongoDB models in `Modules/<Module>/app/Models/MongoDb/`. | If JAV has MongoDB models, put them under `Modules/JAV/app/Models/MongoDb/` and extend `\Modules\Core\app\Models\MongoDb`. |

### 2.3 Routing and Controllers ([02-routing-and-controllers-standard](architecture/02-routing-and-controllers-standard.md))

| Rule | Requirement | Refactor |
|------|-------------|----------|
| **02-ROU-001** | One `routes/web.php` with `render.*` and `action.*` groups. | Restructure web routes into `prefix('render')->name('render.jav.')` and `prefix('action')->name('action.jav.')` (or equivalent). |
| **02-ROU-002** | API under `routes/api_v1.php` with prefix `/api/v1` and names `api.v1.{group}.*`. | Ensure API routes use `api_v1.php`, prefix `api/v1`, and naming `api.v1.jav.*` (or similar). |

### 2.4 Data model and repositories ([03-data-model-standards](architecture/03-data-model-standards.md), [06a-model-standards](architecture/06a-model-standards.md))

- **Table:** Use **`movies`** (not `jav`) for the movie entity.
- **5 models:** Movie (movies), Actor (actors), Tag (tags), MovieActor (movie_actor), MovieTag (movie_tag).
- **One repository per model:** MovieRepository, ActorRepository, TagRepository, MovieActorRepository, MovieTagRepository. JavManager (or MovieManager) calls only these repositories; no direct Eloquent.

Full skeleton (fillable, relations, repository methods) is in [03-jav-fetch-parse-flow §3.4](../reference/03-jav-fetch-parse-flow.md#34-model-and-repository-skeleton-detail).

| Rule | Refactor |
|------|----------|
| **DATA-MOD-001** / **06A-MOD-001** | Model name = singular of table. Movie → movies, Actor → actors, Tag → tags, MovieActor → movie_actor, MovieTag → movie_tag. |
| **06A-MOD-002/003** | Any MongoDB model in `Models/MongoDb/` extending Core `MongoDb`. |
| **BE-REQ-004** | One repository per model; Service/Manager uses repositories only. |

### 2.5 Contracts and Interfaces ([16-contracts-and-interfaces](architecture/16-contracts-and-interfaces.md))

| Rule | Requirement | Refactor |
|------|-------------|----------|
| **IFACE-001** | External boundaries (e.g. HTTP client to onejav/141jav/ffjav) have an interface. | Consider `HttpClientForJavProvider` (or similar) in JAV module, implemented by OnejavClient etc.; or keep concrete clients if policy allows “single impl for now” and document. |
| **IFACE-002/003** | No 1:1 mirror interfaces without multi-impl or cross-module use. | Keep `IItems`, `IItemParsed` only if they have ≥2 implementations or clear event-contract use; otherwise simplify. |

### 2.6 Testing ([05-testing-standards](architecture/05-testing-standards.md))

| Rule | Requirement | Refactor |
|------|-------------|----------|
| **TEST-001** | Feature tests: full flow, no mocking of internal Services/Repositories; mock only external boundaries. | In JAV feature tests, mock only HTTP client (or provider client), not JavManager or repositories. |
| **TEST-002** | Unit test file targets the correct class (e.g. `OnejavServiceTest` → `OnejavService`). | Verify naming and that the class under test is the right one. |
| **TEST-003** | Use Factory + Faker for test data. | Use `Jav::factory()`, etc.; avoid hardcoded entity data where possible. |

### 2.7 File Creation Discipline ([00-project-structure](architecture/00-project-structure.md) 00-STR-003)

- Do not add new classes “for readability” without a “why new class” note (e.g. new SRP, reusable by ≥2 call sites, required boundary). When copying, we are not creating new classes arbitrarily; we are aligning existing ones. For any **new** file added during refactor, document the criterion.

---

## 3. Dependency and Environment Gaps

Cursor’s root `composer.json` already has:

- `jooservices/client`, `jooservices/dto`
- `nwidart/laravel-modules`
- `mongodb/laravel-mongodb`
- `laravel/framework`, `inertiajs/inertia-laravel`, etc.

XCrawlerII JAV relies on:

| Dependency | In Cursor? | Action |
|------------|------------|--------|
| **symfony/dom-crawler** | No | **Add** to Cursor `composer.json` (required for HTML parsing in Adapters). |
| **laravel/scout** | No | **Add** if we keep `Jav::searchable()` and search index; otherwise **stub** `searchable()` (no-op) and add Scout later. |
| **matchish/laravel-scout-elasticsearch** (or similar) | No | Add only if we enable Scout and use Elasticsearch; otherwise defer. |
| **Modules\Core\Facades\Config** | Yes (Cursor has Core) | Ensure JAV uses Core’s Config facade, not Laravel’s `config()` for module-specific keys if that’s the project standard. |
| **JOOservices\Client\*** | Yes | No change. |

**Recommendation:** Add `symfony/dom-crawler` in Cursor. For Scout: either add `laravel/scout` and a driver (e.g. database driver for minimal setup) or stub `searchable()` and document “search to be wired later”.

---

## 4. Phased Plan: What We Can Do

### Phase 1: Copy + Minimal Viable Refactor

- Copy `Modules/JAV` from XCrawlerII to Cursor `Modules/JAV`.
- Add missing Composer deps: `symfony/dom-crawler`; decide Scout (add or stub).
- Register JAV module in Cursor (nwidart: ensure `Modules/JAV` is in the modules path and loaded).
- Align tests: extend `Modules\Core\Tests\TestCase`; ensure no local TestCase.
- Fix any broken namespaces/imports (e.g. Core Config, Core TestCase).
- Run tests and fix obvious failures (DB, config, env).

**Outcome:** JAV module runs in Cursor, tests pass (or are clearly skipped with TODOs).

### Phase 2: Policy-Aligned Refactor

- **Backend layering:** Introduce `JavRepository`, `ActorRepository`, `TagRepository`; JavManager calls them. Remove direct Eloquent from JavManager for persistence (optional but recommended).
- **Domain literals:** Introduce `JavSource` enum (or Constants) for onejav/141jav/ffjav; replace string literals.
- **Routes:** Restructure `web.php` into render/action groups; ensure `api_v1.php` with correct prefix and names.
- **DTO folder:** Use project-standard (e.g. `DTO/` or `Dtos/`); align namespace.
- **Contracts:** Review IItems, IItemParsed; keep only if justified; add interface for provider HTTP client if required by IFACE-001.

**Outcome:** JAV complies with docs (backend layering, routes, constants, tests).

### Phase 3: Search and Optional Features

- If Scout was stubbed: add `laravel/scout` and a driver; implement `Jav::searchable()` and indexing.
- Optional: unified “fetch by path” API, single-item fetch by URL, batch “all three” sync (see [03-jav-fetch-parse-flow](../reference/03-jav-fetch-parse-flow.md) §10).

**Outcome:** Search (and any extra features) working per design.

---

## 5. Step-by-Step Refactor Checklist

Use this when performing the copy and refactor.

### 5.1 Before Copy

- [ ] Read [03-jav-fetch-parse-flow](../reference/03-jav-fetch-parse-flow.md) (terminology, flow, file index).
- [ ] Read [02-backend-layering](architecture/02-backend-layering.md), [00-project-structure](architecture/00-project-structure.md), [05-testing-standards](architecture/05-testing-standards.md).
- [ ] Ensure Cursor has Core module and `Modules\Core\Tests\TestCase`; confirm module discovery (nwidart) path includes `Modules`.

### 5.2 Copy and Dependencies

- [ ] Copy `XCrawlerII/Modules/JAV` to `Cursor/Modules/JAV` (excluding vendor, node_modules, .env).
- [ ] Add `symfony/dom-crawler` to Cursor `composer.json`; run `composer install`.
- [ ] Decide Scout: add `laravel/scout` (+ driver) or stub `Jav::searchable()` and document.
- [ ] Register JAV module so it boots (nwidart: `Modules/JAV` in path; `module.json` providers listed).

### 5.3 Namespace and Core Usage

- [ ] Replace any XCrawlerII-specific config/import with Cursor Core (e.g. `Modules\Core\Facades\Config`).
- [ ] Ensure all tests extend `Modules\Core\Tests\TestCase`.
- [ ] Fix namespace/use statements so they resolve under Cursor (e.g. `Modules\JAV\*`).

### 5.4 Backend Layering and Repositories

- [ ] (Optional but recommended) Add `JavRepository`, `ActorRepository`, `TagRepository`; move persistence from JavManager to these; JavManager only orchestrates.
- [ ] Ensure no Controller/FormRequest uses `DB::` or `Model::query()` directly.
- [ ] Introduce `JavSource` enum (or Constants) for provider names; replace `'onejav'`, `'141jav'`, `'ffjav'` in code and config keys where appropriate.

### 5.5 Routes

- [ ] Restructure `Modules/JAV/routes/web.php` with `render.*` and `action.*` groups per 02-ROU-001.
- [ ] Ensure `Modules/JAV/routes/api_v1.php` (or equivalent) uses prefix `api/v1` and names `api.v1.jav.*` per 02-ROU-002.

### 5.6 Models and DTOs

- [ ] Verify model names match table/collection (singular); MongoDB models in `Models/MongoDb/` extending Core MongoDb.
- [ ] Place DTOs in `app/DTO/` or `app/Dtos/` per project standard; align namespace.

### 5.7 Tests

- [ ] Feature tests: no mocks of JavManager or internal repositories; mock only provider HTTP client (or external boundary).
- [ ] Unit tests: correct class under test; use factories and Faker where applicable.
- [ ] Frontend tests: under `tests/Frontend/unit/` and `tests/Frontend/e2e/`, not inside `resources/js/`.

### 5.8 Contracts and Interfaces

- [ ] Review `IItems`, `IItemParsed`: keep if multi-impl or event contract; remove if 1:1 mirror with no benefit.
- [ ] If policy requires interface for external HTTP: add contract for provider client and bind in JAVServiceProvider.

### 5.9 After Refactor

- [ ] Run full test suite for JAV and Core; fix failures.
- [ ] Run static analysis (e.g. PHPStan/Larastan) if configured.
- [ ] Update [03-jav-fetch-parse-flow](../reference/03-jav-fetch-parse-flow.md) if file paths or component names changed (e.g. Repository layer).

---

## 6. References

| Doc | Purpose |
|-----|--------|
| [docs/README.md](../README.md) | Doc index and “which doc do I read?” |
| [03-jav-fetch-parse-flow](../reference/03-jav-fetch-parse-flow.md) | JAV fetch/parse flow, terminology, diagrams, file index |
| [00-project-structure](architecture/00-project-structure.md) | Module template, DTO, tests, MongoDB location |
| [02-backend-layering](architecture/02-backend-layering.md) | Controller → FormRequest → Service → Repository → Model |
| [02-routing-and-controllers-standard](architecture/02-routing-and-controllers-standard.md) | render/action and api_v1 route rules |
| [03-data-model-standards](architecture/03-data-model-standards.md) | Model naming, TABLE/COLLECTION, fillable |
| [05-testing-standards](architecture/05-testing-standards.md) | Feature vs unit, factories, Core TestCase |
| [16-contracts-and-interfaces](architecture/16-contracts-and-interfaces.md) | When to add/remove interfaces |
| [00-pr-onboarding-playbook](00-pr-onboarding-playbook.md) | General “implement without breaking rules” |

---

*Playbook version: 1.0. Last updated: 2026-03-01.*
