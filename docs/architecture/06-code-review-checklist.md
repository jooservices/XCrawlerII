# 06 - Code Review Checklist

## Purpose

Provide a single checklist for reviewers, mapped to Rule IDs from the architecture docs, with severity (blocker vs non-blocker) so that PRs are evaluated consistently.

## Scope

- All pull requests that touch backend (PHP), frontend (Vue/TS), or docs under the scope of the architecture.

## How to use

- **Blocker:** Must be fixed before merge; violates a hard rule.
- **Non-blocker:** Should be fixed or explicitly accepted with a comment; best practice or soft rule.

---

## Module boundaries

| Check                                                                   | Rule ID          | Severity    | Action                                              |
| ----------------------------------------------------------------------- | ---------------- | ----------- | --------------------------------------------------- |
| No `use Modules\<Feature>\*` from another feature module                | MOD-002, MOD-003 | Blocker     | Reject; move contract to Core or remove dependency. |
| No `use Modules\<Feature>\*` from Core                                  | MOD-004          | Blocker     | Reject; Core must not depend on features.           |
| Inter-feature communication only via Core contracts                     | MOD-003          | Blocker     | Reject if direct feature→feature dependency.        |
| Core has no feature-specific business logic or nouns (except contracts) | MOD-001          | Non-blocker | Request refactor; document exception if temporary.  |

---

## Interfaces (IFACE-900)

Interface creation is a **blocker** unless the following are satisfied. See [16-contracts-and-interfaces](16-contracts-and-interfaces.md) for full Interface Governance policy.

| Check                                                                 | Rule ID    | Severity | Action                                                                 |
| --------------------------------------------------------------------- | ---------- | -------- | ---------------------------------------------------------------------- |
| New/changed interface has justification (external boundary or IFACE-002) | IFACE-900  | Blocker  | Reject; justify per IFACE-001/IFACE-002 or remove interface.          |
| At least one consumer type-hints the interface                        | IFACE-900  | Blocker  | Reject; add consumer or remove interface.                              |
| Interface is bound in correct ServiceProvider (Core or owning module)  | IFACE-400  | Blocker  | Reject; add binding.                                                   |
| At least one test proves the seam is used; external boundaries mockable | IFACE-500  | Blocker  | Reject; add test that uses interface (mock/fake/swap).                 |
| No 1:1 / mirror / micro-interface without benefit                     | IFACE-003  | Blocker  | Reject; remove interface or justify per IFACE-001/IFACE-002.           |
| Shared contracts live in Core only                                    | IFACE-100  | Blocker  | Reject; move contract to Core.                                        |

---

## Backend layering

| Check                                                                        | Rule ID                | Severity    | Action                                         |
| ---------------------------------------------------------------------------- | ---------------------- | ----------- | ---------------------------------------------- |
| No `DB::`, `Model::query()`, `->where(`, raw SQL in Controller               | BE-REQ-001, BE-REQ-002 | Blocker     | Reject; move to Service/Repository.            |
| No business logic or transactions in Controller                              | BE-REQ-002, BE-REQ-003 | Blocker     | Reject; move to Service.                       |
| Transactions only in Service layer                                           | BE-REQ-003             | Blocker     | Reject if in Controller/Repository.            |
| 1 Model ↔ 1 Repository                                                       | BE-REQ-004             | Blocker     | Reject if repository owns multiple aggregates. |
| Authorization in Controller/FormRequest; Service receives authorized context | BE-REQ-005             | Blocker     | Reject if Service does Gate/user->can.         |
| No hardcoded domain literals; use Enums/Constants                            | BE-REQ-006             | Non-blocker | Request refactor.                              |
| Jobs/Commands orchestrate Services; Listeners side-effects only              | BE-REQ-007             | Non-blocker | Request refactor.                              |

---

## Data model standards

| Check                                                                                    | Rule ID      | Severity    | Action                           |
| ---------------------------------------------------------------------------------------- | ------------ | ----------- | -------------------------------- |
| Model name = singular of table/collection (e.g. `client_logs` → `ClientLog`)             | DATA-MOD-001 | Blocker     | Reject; rename model.            |
| Every model has `const TABLE` or `const COLLECTION` and `$table = self::*`               | DATA-MOD-002 | Blocker     | Reject; add constant and assign. |
| MongoDB models extend `\Modules\Core\app\Models\MongoDb` and live in `*/Models/MongoDb/` | DATA-MOD-003 | Blocker     | Reject; fix base class and path. |
| Explicit `$fillable`; no `$guarded = []`                                                 | DATA-MOD-004 | Blocker     | Reject; define fillable.         |
| Timestamps enabled; not in fillable                                                      | DATA-MOD-005 | Non-blocker | Request fix.                     |

---

## Frontend standards

| Check                                                              | Rule ID     | Severity    | Action                                  |
| ------------------------------------------------------------------ | ----------- | ----------- | --------------------------------------- |
| Pages: orchestration/layout only; no business logic or raw HTTP    | FE-ARCH-001 | Non-blocker | Request refactor to Composable/Service. |
| Components: presentational only; no API calls                      | FE-ARCH-002 | Non-blocker | Request refactor.                       |
| Shared assets (layout, httpClient, shared components) only in Core | FE-ARCH-005 | Blocker     | Reject; move to Core or remove sharing. |
| Composition API + `<script setup lang="ts">` only                  | FE-ARCH-006 | Non-blocker | Request refactor.                       |

---

## Testing standards

| Check                                                             | Rule ID  | Severity    | Action                                    |
| ----------------------------------------------------------------- | -------- | ----------- | ----------------------------------------- |
| Feature tests do not mock internal Services/Repositories          | TEST-001 | Blocker     | Reject; remove mock or move to unit test. |
| Unit tests target correct class (e.g. Service test tests Service) | TEST-002 | Non-blocker | Request rename or split.                  |
| Tests use Factory + Faker for data                                | TEST-003 | Non-blocker | Request factory usage.                    |
| Module tests extend `Modules\Core\Tests\TestCase`                 | TEST-004 | Blocker     | Reject; change base class.                |
| Feature has happy, unhappy, security, edge coverage               | TEST-005 | Non-blocker | Request additional tests.                 |
| No placeholder tests (assertTrue(true), empty body)               | TEST-007 | Blocker     | Reject; add real assertion or remove.     |

---

## Documentation

| Check                                                     | Rule ID / Doc          | Severity    | Action                                    |
| --------------------------------------------------------- | ---------------------- | ----------- | ----------------------------------------- |
| New rules go in architecture; reference stays descriptive | 00-docs-classification | Non-blocker | Move content or add rule to architecture. |
| ADR for structural decisions                              | adr/                   | Non-blocker | Request ADR if large structural change.   |

---

## Severity summary

- **Blocker:** Violation of MOD-002, MOD-003, MOD-004, IFACE-900, IFACE-003, IFACE-400, IFACE-500, IFACE-100, BE-REQ-001 to BE-REQ-005, DATA-MOD-001 to DATA-MOD-004, FE-ARCH-005 (shared in Core only), TEST-001, TEST-004, TEST-007. Must fix before merge.
- **Non-blocker:** MOD-001, BE-REQ-006, BE-REQ-007, DATA-MOD-005, FE-ARCH-001, FE-ARCH-002, FE-ARCH-006, TEST-002, TEST-003, TEST-005, TEST-006, docs classification. Should fix or explicitly accept.

---

## References

- [00-docs-classification](00-docs-classification.md)
- [01-module-boundaries](01-module-boundaries-and-dependencies.md)
- [02-backend-layering](02-backend-layering.md)
- [03-data-model-standards](03-data-model-standards.md)
- [04-frontend-standards](04-frontend-standards.md)
- [05-testing-standards](05-testing-standards.md)
- [16-contracts-and-interfaces](16-contracts-and-interfaces.md) — Interface Governance (IFACE-*), placement, binding, IFACE-900 checklist.
- [docs/README.md](../README.md)
