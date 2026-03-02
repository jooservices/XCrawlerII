# 00 - Project Structure

## Canonical Repository Tree

```text
.
├── app/
├── bootstrap/
├── config/
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── docs/
│   ├── architecture/
│   └── skills/
├── Modules/
│   ├── Core/
│   └── <FeatureModule>/
├── public/
├── resources/
│   └── js/
│       ├── app.ts
│       └── bootstrap.ts
├── routes/
├── storage/
├── tests/
└── vendor/
```

## Module Template

```text
Modules/<ModuleName>/
├── app/
│   ├── Constants/
│   ├── Enums/
│   ├── Http/
│   │   ├── Controllers/
│   │   └── Requests/
│   ├── Services/
│   ├── Repositories/
│   ├── Models/
│   ├── Policies/
│   ├── Jobs/
│   ├── Events/
│   ├── Listeners/
│   ├── DTOs/
│   └── Providers/
├── database/
│   ├── migrations/
│   ├── factories/
│   └── seeders/
├── resources/
│   └── js/
│       ├── pages/
│       ├── components/
│       ├── composables/
│       ├── services/
│       └── types/
├── routes/
│   ├── web.php
│   └── api_v1.php
├── tests/
│   ├── Feature/
│   ├── Unit/
│   ├── FormRequest/
│   ├── Frontend/
│   │   ├── unit/
│   │   └── e2e/
└── module.json
```

Test base note:

- Feature module tests extend `Modules/Core/tests/TestCase.php` directly.
- Module-specific test helpers MUST be traits under `tests/Concerns/`; local `tests/TestCase.php` is forbidden unless exception-registered.

## Persistent Models Structure

Rule `00-STR-004`:
Models MUST be structured according to backend separation. See [06a - Model Standards](06a-model-standards.md) for detailed policies.
MongoDB models MUST be placed in `Modules/<ModuleName>/app/Models/MongoDb/`.

## Core Module Structure

Contract placement and naming for `Contracts/` are defined in [16 - Contracts and Interfaces (Module-wide)](16-contracts-and-interfaces.md).

```text
Modules/Core/
├── app/
│   ├── Constants/
│   ├── Enums/
│   ├── Contracts/
│   ├── DTOs/
│   ├── Support/
│   │   ├── Logging/
│   │   └── Errors/
│   └── Providers/
├── resources/
│   └── js/
│       ├── layouts/MasterLayout.vue
│       ├── components/base/
│       ├── components/shared/
│       ├── composables/
│       └── services/httpClient.ts
└── tests/
    └── TestCase.php
```

## FE Test Separation

Rule `00-STR-001`:
Frontend tests MUST be outside FE implementation directories and stored under each module `tests/Frontend/{unit,e2e}`.

Rationale:
Prevents mixing product code and test code; keeps ownership and CI targeting clean.

Allowed:

```text
Modules/Billing/resources/js/components/InvoiceTable.vue
Modules/Billing/tests/Frontend/unit/InvoiceTable.spec.ts
```

Forbidden:

```text
Modules/Billing/resources/js/components/InvoiceTable.spec.ts
```

Verification:

- `rg --files Modules | rg 'resources/js/.+\.spec\.'` returns no results.
- FE tests exist under `tests/Frontend`.

## Core FE Ownership

Rule `00-STR-002`:
`Modules/Core/resources/js` MUST own master layout, base/shared components, shared composables, and HTTP wrapper.

Rationale:
Shared UX and shared FE infra must remain centralized.

Allowed:

```text
Modules/Core/resources/js/layouts/MasterLayout.vue
Modules/Core/resources/js/components/base/BaseButton.vue
```

Forbidden:

```text
Modules/Auth/resources/js/layouts/MasterLayout.vue
```

Verification:

- Only Core contains `layouts/MasterLayout.vue`.
- Feature modules only contain feature-specific pages/components.

## File Creation Discipline

Rule `00-STR-003`:
Reuse analysis is required BEFORE creating any new class/file. New class/file creation requires a "why new class" note in PR or feature spec citing one approved criterion.

Rationale:
Avoids class sprawl and fragmented responsibilities.

Allowed:

```md
Reuse analysis: existing `RetryService` cannot hold payment-specific invariants safely.
Why new class (criterion: reusable by >=2 call sites): PaymentRetryPolicy reused by BillingService and WebhookReplayService.
```

Forbidden:

```md
Created 8 helper classes for readability.
```

Verification:

- PR/spec includes reuse analysis and selected criterion for each new class/file:
    - new SRP responsibility
    - reusable by >=2 call sites
    - maintainability threshold exceeded
    - required boundary/adapter seam
- `rg "class TestCase" Modules/*/tests` returns only `Modules/Core/tests/TestCase.php` unless exception-registered.
