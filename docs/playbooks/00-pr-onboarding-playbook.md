# 00 - PR onboarding playbook

This playbook is a step-by-step guide to implementing a change without violating the project’s architecture rules. Use it before coding, while coding, before opening a PR, and after review.

---

## Before coding

1. **Identify the type of change**
    - New feature (new module or new capability in existing module)?
    - Bug fix or refactor in existing code?
    - New API endpoint, new model, or new UI flow?

2. **Locate the rules that apply**
    - Read [docs/README.md](../README.md) and choose:
        - **Module boundaries:** [docs/architecture/01-module-boundaries.md](../architecture/01-module-boundaries.md) — no feature→feature deps; shared only in Core.
        - **Backend:** [docs/architecture/02-backend-layering.md](../architecture/02-backend-layering.md) — Controller → FormRequest → Service → Repository → Model; no DB in controller.
        - **Models:** [docs/architecture/03-data-model-standards.md](../architecture/03-data-model-standards.md) — TABLE/COLLECTION constant, fillable, Mongo in `Models/MongoDb/`.
        - **Frontend:** [docs/architecture/04-frontend-standards.md](../architecture/04-frontend-standards.md) — Page/Component/Composable/Service; shared assets only in Core.
        - **Testing:** [docs/architecture/05-testing-standards.md](../architecture/05-testing-standards.md) — feature tests without mocking internal services; extend Core TestCase; Factory + Faker.

3. **Check module boundaries**
    - If your change touches another feature module: do **not** add a direct dependency. Use a contract in Core if the features must interact.
    - If you need a shared Vue component or shared http client: it belongs in **Core**, not in a feature module.

4. **Plan layers (backend)**
    - New endpoint → Controller (thin) + FormRequest + Service + Repository (+ Model if new entity).
    - New MongoDB model → in `Modules/<Module>/app/Models/MongoDb/`, extend `\Modules\Core\app\Models\MongoDb`, define `COLLECTION` and `$fillable`.

5. **Plan tests**
    - Feature tests: full flow, no mocks of internal Service/Repository; use test DB and factories.
    - Unit tests: target the correct class (e.g. Service, FormRequest); mock only external boundaries.
    - Cover: happy, unhappy, validation, authorization, edge/security where relevant.

---

## While coding

1. **Controllers**
    - Only: receive request, validate via FormRequest, call Service, return response. No `DB::`, no `Model::query()`, no transactions.

2. **Services**
    - Own business logic and transactions. Call Repositories only. Do not perform authorization (that is in Controller/FormRequest).

3. **Repositories**
    - One repository per Model. No transaction boundaries; Service owns transactions.

4. **Models**
    - Define `const TABLE` or `const COLLECTION` and `$table = self::*`. Explicit `$fillable`; no `$guarded = []`. MongoDB models in `*/Models/MongoDb/` extending Core `MongoDb`.

5. **Frontend**
    - Pages: orchestration only; use Composables and Services. Components: presentational only; no API calls. Shared layout/httpClient: only in Core.

6. **Tests**
    - Module tests extend `Modules\Core\Tests\TestCase`. Use factories and Faker. No placeholder tests (`assertTrue(true)`).

7. **No hardcoded domain literals**
    - Use Enums or Constants for statuses, types, etc.

---

## Before opening the PR

1. **Run local quality gates**
    - Lint (e.g. Pint), static analysis (e.g. PHPStan), tests (PHPUnit with test env). Fix any failures.

2. **Self-review with [06-code-review-checklist](../architecture/06-code-review-checklist.md)**
    - Module boundaries: no feature→feature or Core→feature deps.
    - Backend: no DB/business logic in Controller; transactions in Service only; 1 Model ↔ 1 Repository.
    - Data: TABLE/COLLECTION, fillable, Mongo path and base class.
    - Frontend: shared assets only in Core; Page/Component/Composable/Service roles respected.
    - Tests: no internal mocks in feature tests; correct base class; no placeholders.

3. **Checklist**
    - [ ] All new code follows the layering (Controller → Service → Repository → Model).
    - [ ] New models have TABLE/COLLECTION constant and explicit fillable.
    - [ ] MongoDB models in `*/Models/MongoDb/` and extend Core `MongoDb`.
    - [ ] No new cross-feature dependencies; any shared contract is in Core.
    - [ ] Tests extend `Modules\Core\Tests\TestCase` where in a module.
    - [ ] Feature tests do not mock internal Services/Repositories.
    - [ ] No placeholder or empty tests.

4. **PR description**
    - Briefly describe the change and which area (module, layer) it touches. If you had to deviate from a rule, note the exception and link to the rule ID (and add to exceptions registry if the project requires it).

---

## After review

1. **Address blocker comments**
    - All items marked “blocker” or “must fix” in the [06-code-review-checklist](../architecture/06-code-review-checklist.md) must be fixed before merge.

2. **Non-blockers**
    - Fix or explicitly accept with a comment (e.g. “Deferring to follow-up ticket”).

3. **Merge**
    - Ensure CI is green. Merge according to project branch policy (e.g. feature → develop).

4. **Follow-up**
    - If an exception was approved, ensure it is recorded in the project’s exceptions registry (if applicable) with owner, scope, and expiry.

---

## References

- [docs/README.md](../README.md) — Which doc do I read?
- [docs/architecture/06-code-review-checklist.md](../architecture/06-code-review-checklist.md) — Full checklist and severity
- [docs/architecture/00-docs-classification.md](../architecture/00-docs-classification.md) — Where to put new docs
