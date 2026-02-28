# 16 - Contracts and Interfaces (Module-wide)

## Purpose

This standard defines where and how PHP interfaces (contracts) MUST be placed and named in **any** module. A single canonical path per module (`*/Contracts/`) and consistent naming reduce drift, simplify discovery, and make PR review and code generation predictable.

## Scope

- **In scope:** All interfaces owned by a module, in any module (Core, Inventory, Analytics, Billing, etc.).
- **Out of scope:** PSR/third-party interfaces (e.g. `Psr\*`).

---

## Interface Governance

**Default stance:** No interface by default. An interface is allowed only when it provides real decoupling value AND has real usage (consumer + binding + tests). This policy is enforceable; PRs that add interfaces without satisfying the criteria below MUST be blocked (see IFACE-900).

### When an interface MUST exist

Rule `IFACE-001`:
An interface MUST exist for **external boundaries** — any integration point outside the application process that the codebase can replace or mock for tests or environments.

Examples (non-exhaustive):

- Third-party API client (HTTP client for external service)
- Filesystem / storage abstraction (e.g. cloud storage, local disk)
- Search engine client (Elasticsearch, Algolia, etc.)
- Payment gateway, SMS gateway, email provider
- Message queue or event bus adapter
- Clock / time provider (when needed for deterministic tests)
- UUID generator (when needed for deterministic tests)

Rationale:
External boundaries are replaceable by design; tests and alternate implementations require a single contract.

Verification:
- Every such boundary is represented by an interface in the owning module (or Core if shared). Implementation is bound in the appropriate ServiceProvider; consumers type-hint the interface.

---

### When an interface MAY exist

Rule `IFACE-002`:
An interface MAY exist only when at least one of the following holds:

1. **Multi-implementation now or approved near-term plan:** There are (or will be within the same/epic) at least two concrete implementations (e.g. live implementation + test double, or two alternative backends).
2. **Cross-module boundary:** The contract is used by more than one module; it MUST live in Core per [01-module-boundaries-and-dependencies](01-module-boundaries-and-dependencies.md) (MOD-004).
3. **Hard-to-test seam:** Clock, UUID, or similar where swapping implementation is required for deterministic tests and the seam is documented.

Rationale:
Interfaces without multiple implementations or clear cross-boundary use add indirection without decoupling benefit.

Verification:
- For every interface that is not an external boundary (IFACE-001), the PR justifies it against one of the above. No “we might need it later” without an approved plan.

---

### When an interface MUST NOT exist

Rule `IFACE-003`:
An interface MUST NOT exist in these cases:

- **1:1 interface anti-pattern:** Exactly one implementation exists and no second implementation is planned or justified (IFACE-002).
- **Mirror interface:** The interface merely mirrors a concrete class’s public API with no abstraction benefit (e.g. `UserRepositoryInterface` with only `UserRepository` ever implementing it and no test double).
- **Micro-interface without benefit:** Tiny interface with one method and no realistic swap (unless it is an external boundary per IFACE-001).
- **Interface without consumer:** No class (or test) type-hints the interface; only the implementation is referenced.

Rationale:
Such interfaces create “interface spam,” increase maintenance, and give no decoupling or testability benefit.

Verification:
- Code review and/or static checks: every interface has at least one consumer (excluding the binding registration) and a justified reason per IFACE-001 or IFACE-002.

---

### Core placement and shared contracts

Rule `IFACE-100`:
Core contracts live under `Modules/Core/app/Contracts/**`. Feature modules MUST NOT host shared contracts; interfaces used by more than one module MUST live in Core. Feature modules MAY host module-private interfaces (single-module use) only when justified by IFACE-001 or IFACE-002.

Rationale:
Single ownership of shared contracts and domain-agnostic Core; see [01-module-boundaries-and-dependencies](01-module-boundaries-and-dependencies.md).

Verification:
- Shared contract paths start with `Modules/Core/app/Contracts/`. No feature module contains a contract that is referenced from another feature module.

---

### Naming guidance

Rule `IFACE-101`:
Prefer semantic role suffixes in the interface name: `*Port`, `*Client`, `*Gateway`, `*Provider`, `*Reader`, `*Writer`, as appropriate. The mandatory `Interface` suffix (16-CON-003) still applies — e.g. `PaymentProviderPortInterface`, `HttpClientInterface`. Avoid a bare noun + `Interface` when a role suffix is clearer (e.g. prefer `SmsGatewayPortInterface` over `SmsInterface`).

Rationale:
Role-based names make intent and usage (adapter, gateway, port) explicit and reduce naming drift.

Verification:
- Interface names use a clear role suffix where applicable; file and class names remain compliant with 16-CON-003 and 16-CON-004.

---

### Binding and usage

Rule `IFACE-400`:
Every Core contract MUST be bound in `Modules/Core`’s service provider (or the appropriate Core registration). Every feature-module contract that is injected MUST be bound in that module’s service provider. Consumers MUST type-hint the interface, not the concrete implementation.

Rationale:
Unbound interfaces or consumers depending on concretions defeat the purpose of the contract and break testability.

Verification:
- No interface used for DI is left unbound. No consumer in production code type-hints the concrete class when an interface exists for that seam.

---

### Testing

Rule `IFACE-500`:
A new interface MUST have tests that prove the seam is used (e.g. unit test with a mock/fake of the interface, or integration test that swaps implementation). External boundaries (IFACE-001) MUST be mockable in tests; feature tests MUST NOT mock internal services/repositories (see [05-testing-standards](05-testing-standards.md) TEST-001).

Rationale:
Interfaces without test usage are dead abstraction; external boundaries must be swappable for tests.

Verification:
- PR that adds an interface includes at least one test that uses the interface (mock, fake, or bound alternative). External boundary interfaces are used in tests to isolate the unit from the real dependency.

---

### PR checklist / blockers (IFACE-900)

Rule `IFACE-900`:
Before merge, the following MUST be satisfied for every **new or materially changed** interface; otherwise the PR MUST be blocked.

- [ ] **Justification:** The interface is justified under IFACE-001 (external boundary) or IFACE-002 (multi-impl, cross-module, or hard-to-test seam). If IFACE-002, the justification is stated in the PR (e.g. “cross-module contract in Core” or “two implementations: LiveX and FakeX for tests”).
- [ ] **Consumer:** At least one consumer (service, controller, or test double) type-hints the interface. No interface exists only as “implemented by X” with no caller.
- [ ] **Binding:** The interface is bound to a concrete implementation in the correct ServiceProvider (Core or owning module).
- [ ] **Tests:** At least one test proves the seam is used (mock/fake of the interface or swapped implementation). For external boundaries, tests demonstrate that the boundary is mockable.

Checklist ID for PR/checklist references: **IFACE-900**.

Enforcement:
- Code review and [06-code-review-checklist](06-code-review-checklist.md) require the interface governance checks. Violations are **blocker** severity.

---

## Rules

### Location

Rule `16-CON-001`:
All module interfaces MUST be placed under `Modules/{Module}/app/Contracts/`. No other `Contracts` path is allowed.

Rationale:
One canonical place per module avoids scattered `Contracts` folders and duplicate naming.

Allowed:

- Flat: `Modules/Inventory/app/Contracts/StockRepositoryInterface.php`
- Grouped (one level): `Modules/Analytics/app/Contracts/Repositories/EventRepositoryInterface.php`

Forbidden (MUST NOT):

- `Modules/{Module}/app/Services/Contracts/...`
- `Modules/{Module}/app/Repositories/Contracts/...`
- Any `Contracts` folder outside `Modules/{Module}/app/Contracts/`

Verification:

- `rg --files 'Contracts/' Modules/` returns only paths under `Modules/*/app/Contracts/`.

---

Rule `16-CON-002`:
Group subfolders inside `Contracts/` are OPTIONAL. When used, group folder names MUST be plural and MUST be one level only (no nested groups).

Rationale:
Plural group names align with Laravel conventions; one-level grouping keeps the tree simple and enforceable.

Allowed:

- `Contracts/Repositories/ConfigRepositoryInterface.php`
- `Contracts/Services/ConfigServiceInterface.php`
- `Contracts/Clients/HttpClientInterface.php`

Forbidden:

- `Contracts/Repository/...` (singular)
- `Contracts/Services/Client/ClientInterface.php` (nested group; use `Contracts/Clients/ClientInterface.php` or flat)

Verification:

- Any directory under `Contracts/` is plural and one level deep only.

---

### Naming

Rule `16-CON-003`:
Interface names MUST be StudlyCase and MUST end with the suffix `Interface`. The file name MUST match the interface name 1:1 (one interface per file). The `I*` prefix style (e.g. `IConfigRepository`) is forbidden.

Rationale:
Immediate recognition and consistent autoloading; no ambiguity with concrete class names.

Allowed:

- `ConfigRepositoryInterface` in file `ConfigRepositoryInterface.php`
- `HttpClientInterface` in file `HttpClientInterface.php`

Forbidden:

- `ConfigRepository` (missing `Interface` suffix)
- `IConfigRepository` (prefix style not allowed)
- `ClientContract` for new code (see migration guidance below)
- Two interfaces in one file

Verification:

- Filename equals `{InterfaceName}.php`; interface name ends with `Interface`.

---

Rule `16-CON-004`:
Repository (and other) interface names MUST be semantic (e.g. `ConfigRepositoryInterface`, `EventRepositoryInterface`). Vague base names (e.g. `RepositoryInterface`) are allowed only when they define a true shared base contract used by multiple implementations in that module.

Rationale:
Semantic names improve discoverability and intent clarity.

Allowed:

- `ConfigRepositoryInterface`, `LogRepositoryInterface`, `EventRepositoryInterface`

Forbidden (unless truly a shared base):

- `RepositoryInterface` as the only repository contract in the module

Verification:

- Names reflect the aggregate or capability (Config, Log, Event, etc.).

---

### Migration: `*Contract` → `*Interface`

Rule `16-CON-005`:
New interfaces MUST use the `Interface` suffix and MUST NOT be named `*Contract`. Existing code using `*Contract` MUST, when touched, be migrated: rename to `*Interface` and move the file under `Modules/{Module}/app/Contracts/` (with optional group). Until migration, any bypass MUST be registered in `11-exceptions-registry.md` with rule ref `16-CON-*`.

---

## Folder Layout Examples

**Correct (flat):**

```text
Modules/Inventory/app/Contracts/
├── StockRepositoryInterface.php
└── ReservationInterface.php
```

**Correct (grouped, one level):**

```text
Modules/Analytics/app/Contracts/
├── Repositories/
│   ├── EventRepositoryInterface.php
│   └── MetricRepositoryInterface.php
└── Writers/
    └── ReportWriterInterface.php
```

**Incorrect:**

```text
❌ Modules/Billing/app/Services/Contracts/PaymentGatewayContract.php
❌ Modules/Core/app/Repositories/Contracts/ConfigRepositoryInterface.php
❌ Modules/Analytics/app/Contracts/Repository/EventRepositoryInterface.php   (singular group)
❌ Modules/Analytics/app/Contracts/Services/Client/ClientInterface.php     (nested group)
```

---

## Naming Examples

| Intent            | ✅ Correct                      | ❌ Incorrect                       |
| ----------------- | ------------------------------- | ---------------------------------- |
| Config repository | `ConfigRepositoryInterface`     | `ConfigRepository`, `IConfigRepo`  |
| HTTP client       | `HttpClientInterface`           | `ClientContract`, `IHttpClient`    |
| Report writer     | `ReportWriterInterface`         | `ReportWriter`                     |
| File name         | `ConfigRepositoryInterface.php` | `ConfigRepository.php` (no suffix) |

---

## Interface → Implementation → Binding

**Canonical mapping (within the owning module):**

- **Interface:** `Modules/Inventory/app/Contracts/Repositories/StockRepositoryInterface.php`
- **Concrete:** `Modules/Inventory/app/Repositories/StockRepository.php`
- **Binding:** In that module’s service provider (e.g. `InventoryServiceProvider::register()`): `$this->app->singleton(StockRepositoryInterface::class, StockRepository::class);`

Example:

```php
// Modules/Inventory/app/Contracts/Repositories/StockRepositoryInterface.php
namespace Modules\Inventory\Contracts\Repositories;

interface StockRepositoryInterface
{
    public function findBySku(string $sku): ?Stock;
    public function reserve(string $sku, int $qty): void;
}
```

```php
// Modules/Inventory/app/Repositories/StockRepository.php
namespace Modules\Inventory\Repositories;

use Modules\Inventory\Contracts\Repositories\StockRepositoryInterface;

class StockRepository implements StockRepositoryInterface
{
    // ...
}
```

```php
// In InventoryServiceProvider::register() (or the owning module’s service provider)
$this->app->singleton(
    \Modules\Inventory\Contracts\Repositories\StockRepositoryInterface::class,
    \Modules\Inventory\Repositories\StockRepository::class
);
```

Consumers type-hint the interface; the container resolves to the concrete implementation.

---

## Enforcement Checklist (PR / AI)

**Interface governance (IFACE-900) — blocker if any unchecked:**

- [ ] Every new or changed interface is justified under IFACE-001 (external boundary) or IFACE-002 (multi-impl / cross-module / hard-to-test seam).
- [ ] At least one consumer type-hints the interface; no interface without a caller.
- [ ] Interface is bound in the correct ServiceProvider (Core or owning module).
- [ ] At least one test proves the seam is used (mock/fake or swapped impl); external boundaries are mockable.

**Placement and naming (16-CON-*):**

- [ ] Every new or moved interface lives under `Modules/{Module}/app/Contracts/` (optional plural one-level group).
- [ ] No `Contracts` directory exists under `Services/`, `Repositories/`, or elsewhere under any module’s `app/`.
- [ ] Interface name is StudlyCase and ends with `Interface`; file name matches interface name.
- [ ] No `I*` prefix; no new `*Contract` names.
- [ ] Group folders under `Contracts/` are plural and one level only.
- [ ] Repository (and similar) interface names are semantic; role suffixes (Port, Client, Gateway, etc.) used where appropriate (IFACE-101).
- [ ] Binding is registered in that module’s service provider when the interface is used for DI.

---

## FAQ

**Why not `*/Repositories/Contracts/` or `*/Services/Contracts/`?**  
One canonical `Contracts/` root per module avoids duplicate trees and naming collisions. Implementations stay in Repositories/Services; the contract is the abstraction and belongs in one place per module.

**Why require the `Interface` suffix?**  
Clear naming and file discovery; aligns with common PHP practice and static analysis. No ambiguity with concrete class names.

**When should we create a group folder?**  
When you have several interfaces of the same role (e.g. multiple repositories or clients). For one or two interfaces, a flat `Contracts/` is acceptable.

**What about existing `*Contract` names?**  
When touched, rename to `*Interface` and move under `*/Contracts/`. Until then, register any bypass in `11-exceptions-registry.md`.

**Where do cross-module interfaces go?**  
Interfaces used by more than one module MUST live in Core per `01-module-boundaries-and-dependencies.md`. This doc defines placement and naming **within** any module (including Core).

---

## Related documents

- [01-module-boundaries-and-dependencies](01-module-boundaries-and-dependencies.md) — Core scope, shared contract placement (MOD-004), inter-feature communication.
- [11-exceptions-registry](11-exceptions-registry.md) — Exceptions to interface or contract rules must be registered here.
- [12-git-hooks-and-quality-gates](12-git-hooks-and-quality-gates.md) — Quality gates and PR merge requirements; interface governance is enforced via code review and [06-code-review-checklist](06-code-review-checklist.md).

---

## Exception Link

Exceptions to this document MUST be registered in `11-exceptions-registry.md` with rule ref `16-CON-*` or `IFACE-*`.
