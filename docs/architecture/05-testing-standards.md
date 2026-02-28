# 05 - Testing Standards

## Purpose

Define how backend and frontend tests are structured, what they must cover, and how they align with the architecture (feature tests without mocking internal services, unit tests on the right classes, factories and Faker, Core TestCase, .env.testing).

## Scope

- All tests in `tests/`, `Modules/*/tests/`, and any frontend test directories.
- PHPUnit (backend), and project-standard frontend test setup (e.g. Vitest, E2E) if applicable.

## Non-goals

- Specific coverage thresholds (see project or CI policy)
- Third-party test double libraries beyond project standard (e.g. Mockery for boundaries only)

## Definitions

| Term             | Meaning                                                                                                          |
| ---------------- | ---------------------------------------------------------------------------------------------------------------- |
| **Feature test** | End-to-end flow through HTTP/Inertia or public API; uses real (or test DB) services where possible.              |
| **Unit test**    | Isolated test of a single class or small unit; mocks only external boundaries (e.g. gateways, third-party APIs). |
| **Factory**      | Eloquent or custom factory used to build test data (e.g. `User::factory()`).                                     |
| **Faker**        | Use of Faker for realistic, varied test data.                                                                    |

---

## Rules

### TEST-001: Feature tests run full flow without mocking internal services

**Rule:** Feature tests MUST run the full request flow (Controller → FormRequest → Service → Repository → Model) without mocking internal services (e.g. do not mock `OrderService` in a feature that creates an order). Mock only true external boundaries (HTTP to third parties, payment gateway, etc.).

**Rationale:** Catches integration bugs; internal layers are part of the feature contract.

**Allowed:**

- Feature test: POST to `/api/orders` with valid payload; assert DB state and response; use real `OrderService` and repositories (against test DB).
- Mock: `SmsGatewayPort`, `PaymentGatewayPort`, or external HTTP client.

**Anti-examples (forbidden):**

- Feature test that mocks `OrderService` and then asserts “order created” (no real creation).
- Feature test that mocks `UserRepository` in a login flow (use real repo with test data).

**Enforcement:** Code review; grep for `mock(.*Service)` or `mock(.*Repository)` in Feature tests.  
**References:** [02-backend-layering](02-backend-layering.md).

---

### TEST-002: Unit tests target the correct classes

**Rule:** Unit tests MUST target the correct class: e.g. test `OrderService` in `OrderServiceTest`, `LoginRequest` in `LoginRequestTest`. They must not be mislabeled (e.g. “LoginControllerTest” that only tests validation is really a FormRequest or Service test).

**Rationale:** Clear ownership and coverage; avoids false sense of coverage on controllers.

**Allowed:**

- `OrderServiceTest` testing `OrderService` methods with mocked repositories.
- `LoginRequestTest` testing validation rules and `authorize()`.

**Anti-examples (forbidden):**

- `LoginControllerTest` that only asserts validation (belongs in FormRequest test).
- Unit test that exercises full HTTP stack (that is a feature test).

**Enforcement:** Code review; file naming and class-under-test must match.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

### TEST-003: Factory and Faker always for data

**Rule:** Tests MUST create data using Factories and Faker (or equivalent) for realism and variation. Avoid hardcoded inline data for entities; use factory methods and Faker where appropriate.

**Rationale:** Reduces brittle fixtures; improves coverage of edge cases (e.g. long names, unicode).

**Allowed:**

- `User::factory()->create();`, `Order::factory()->withItems(3)->create();`
- Faker in factory: `'name' => $this->faker->name()`

**Anti-examples (forbidden):**

- `User::create(['email' => 'a@b.com', 'name' => 'Test'])` without factory when a factory exists.
- Large static JSON fixtures checked into repo when a factory could generate equivalent data.

**Enforcement:** Code review; prefer factory usage in new tests.  
**References:** [03-data-model-standards](03-data-model-standards.md).

---

### TEST-004: Backend tests extend Core TestCase

**Rule:** All backend tests in modules MUST extend `Modules\Core\Tests\TestCase` (or the project’s designated base), which in turn extends the application base TestCase. Do not extend `Tests\TestCase` directly from module tests unless the project explicitly allows it.

**Rationale:** Shared bootstrap, DB refresh, and helpers stay consistent.

**Allowed:**

```php
use Modules\Core\Tests\TestCase;

final class OrderFeatureTest extends TestCase
{
    // ...
}
```

**Anti-examples (forbidden):**

```php
use Tests\TestCase;

final class OrderFeatureTest extends TestCase  // in Modules/*/tests
{
    // ...
}
```

**Enforcement:** Grep in `Modules/*/tests` for `extends \Tests\TestCase`; should be empty.  
**References:** [01-module-boundaries](01-module-boundaries.md).

---

### TEST-005: Cover happy, unhappy, weird, security/exploit, edge cases

**Rule:** Every feature (or major use case) MUST have tests covering: happy path, unhappy path (validation, not found, forbidden), weird/invalid input, security/exploit (e.g. IDOR, mass assignment), and edge cases (empty list, boundaries). No “happy path only” acceptance.

**Rationale:** Production failures often occur outside the happy path.

**Allowed:**

- Happy: valid request, 200/201, correct state.
- Unhappy: 422 validation, 404, 403.
- Weird: empty body, wrong content-type, huge payload.
- Security: unauthorized access, wrong tenant, invalid ID.
- Edge: pagination last page, empty result set.

**Anti-examples (forbidden):**

- Only `test_create_order_success` with no validation or authorization tests.

**Enforcement:** Code review; test plan or checklist per feature.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

### TEST-006: .env.testing usage

**Rule:** Backend tests MUST run against a dedicated test environment. Use `.env.testing` (or equivalent) so that tests do not use production or development DB/cache. Document in project README or CI how `.env.testing` is loaded (e.g. `APP_ENV=testing`).

**Rationale:** Prevents accidental data loss and flaky tests due to shared state.

**Allowed:**

- `phpunit.xml` or bootstrap sets `APP_ENV=testing` and loads `.env.testing` when present.
- CI sets test DB and cache (e.g. SQLite in memory, separate Redis DB).

**Anti-examples (forbidden):**

- Running PHPUnit with default `.env` pointing at dev or prod DB.
- No `.env.testing` and no documentation (ASSUMPTION: verify in project whether `.env.testing` exists; if not, add a note to create it).

**Enforcement:** CI must run tests with `APP_ENV=testing`; document in README.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

### TEST-007: No placeholder or incomplete tests

**Rule:** Placeholder or incomplete tests are FORBIDDEN. Every test MUST assert something meaningful; no `$this->assertTrue(true);` or empty test bodies. Skipping is allowed only with a clear reason (e.g. “Blocked by X; ticket Y”).

**Rationale:** Placeholders create false confidence.

**Allowed:**

- Real assertion: `$this->postJson(...)->assertStatus(422)->assertJsonValidationErrors(['email']);`

**Anti-examples (forbidden):**

- `public function test_something(): void { $this->assertTrue(true); }`
- Empty test or test that only runs code without asserting.

**Enforcement:** Code review; optional CI grep for placeholder patterns.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

## Backend Test Base

- Extend `Modules\Core\Tests\TestCase` for all module tests.
- Use `TestCase` from the application for root `tests/` if not using modules for that area.

## .env.testing

- If the project does not yet have `.env.testing`, create it (or document that tests use `APP_ENV=testing` and another mechanism). Ensure DB and cache are isolated from dev/prod.

---

## Enforcement

- **PR:** Checklist in [06-code-review-checklist](06-code-review-checklist.md).
- **CI:** Run PHPUnit with `APP_ENV=testing`; optional coverage and “no mock of internal services” checks.
- **References:** [02-backend-layering](02-backend-layering.md), [docs/reference/00-system-overview](../reference/00-system-overview.md).
