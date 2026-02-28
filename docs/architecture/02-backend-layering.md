# 02 - Backend Layering

## Purpose

Enforce a strict request lifecycle and layer boundaries so that controllers stay thin, business logic lives in services, and persistence is isolated in repositories.

## Scope

- All backend code (PHP) in `app/` and `Modules/*/app/`.
- HTTP request handling, validation, services, repositories, models, jobs, and commands.

## Non-goals

- Frontend layering (see [04-frontend-standards](04-frontend-standards.md))
- API URL design or HTTP status codes (covered by API contracts if present)

## Definitions

| Term                  | Meaning                                                                                       |
| --------------------- | --------------------------------------------------------------------------------------------- |
| **Controller**        | HTTP entry point; delegates to FormRequest + Service; no business logic, no DB.               |
| **FormRequest**       | Validates and authorizes the request; provides validated input.                               |
| **Service / UseCase** | Orchestrates business logic; owns transactions; calls Repositories.                           |
| **Repository**        | Data access for one aggregate/model; returns Models or DTOs.                                  |
| **Model**             | Eloquent (SQL) or Mongo model; table/collection mapping, fillable, no business orchestration. |

---

## Golden Path: Request Lifecycle

```
HTTP Request
    → Controller (thin: validate + delegate)
    → FormRequest (validation + authorization)
    → Service / UseCase (business logic, transactions)
    → Repository (queries, persistence)
    → Model (data shape, table/collection)
    → Response
```

- **Controller:** Receives request, uses FormRequest for validation/authorization, calls Service with authorized context, returns response (redirect/JSON/Inertia).
- **FormRequest:** Rules + `authorize()`; no business logic.
- **Service:** Application/domain logic; `DB::transaction()` only here; uses Repositories only (no direct `Model::query()` in controller or in callers of repository).
- **Repository:** 1 Model <-> 1 Repository; methods like `findById`, `create`, `getPaginated`.
- **Model:** TABLE/COLLECTION constant, fillable, timestamps; no orchestration.

Jobs and Commands orchestrate Services; they do not contain business logic. Listeners perform side-effects only (e.g. logging, notifications), not core workflow.

---

## Rules

### BE-REQ-001: Strict layering order

**Rule:** Execution flow MUST follow: Controller → FormRequest → Service/UseCase → Repository → Model. No layer may skip another (e.g. Controller must not call Repository or Model directly).

**Rationale:** Clear separation of transport, validation, orchestration, and persistence.

**Allowed:**

```php
// Controller
public function store(StoreOrderRequest $request, OrderService $service): RedirectResponse
{
    $service->create($request->validated(), $request->user());
    return redirect()->route('orders.index');
}
```

**Anti-examples (forbidden):**

```php
// Controller
public function store(Request $request): RedirectResponse
{
    Order::query()->create($request->all());
    return redirect()->route('orders.index');
}
```

**Enforcement:** Code review; grep for `DB::`, `Model::query()`, `->where(`, `->create(` in Controller classes.  
**References:** [03-data-model-standards](03-data-model-standards.md).

---

### BE-REQ-002: Thin controllers

**Rule:** Controllers MUST NOT contain business logic or direct DB queries. No `DB::`, no `Model::query()`, no raw SQL, no transaction boundaries in controllers.

**Rationale:** Keeps controllers testable and single-purpose; logic and transactions belong in Services.

**Allowed:**

- Calling a Service method with validated input and user/context.
- Returning redirect, JSON, or Inertia response.
- Reading request (via FormRequest) and passing to Service.

**Anti-examples (forbidden):**

- `DB::transaction(...)` in controller.
- `User::query()->where(...)->first()` in controller.
- Any if/else that encodes business rules (e.g. “if balance &lt; 0 then reject”) in controller.

**Enforcement:** Code review; static grep in `*Controller.php` for `DB::`, `::query(`, `->where(`, `->orderBy(`.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

### BE-REQ-003: Transactions in Service layer only

**Rule:** Transaction boundaries MUST be in the Service (or UseCase) layer only. Repositories perform single-operation persistence; Services wrap multi-step or multi-repository work in a transaction.

**Rationale:** Cross-repository consistency is a business concern; only the Service has the full picture.

**Allowed:**

```php
// In OrderService
$this->db->transaction(function () use ($payload) {
    $order = $this->orderRepository->create($payload);
    $this->inventoryRepository->reserve($order->id, $payload['items']);
});
```

**Anti-examples (forbidden):**

- `DB::transaction()` in a Controller or in a Repository that orchestrates multiple aggregates.
- Repository starting a transaction that spans multiple repositories.

**Enforcement:** Code review; grep for `DB::transaction` only in `*Service*.php` or `*UseCase*.php`.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

### BE-REQ-004: One Model per Repository

**Rule:** Maintain a 1 Model ↔ 1 Repository mapping. A repository must not own or return multiple unrelated models/aggregates.

**Rationale:** Clear ownership and testability; avoids “god” repositories.

**Allowed:**

- `UserRepository` → `User` model only.
- `OrderRepository` → `Order` model only (with relations if same aggregate).

**Anti-examples (forbidden):**

- `SharedRepository` with methods `getUsers()`, `getOrders()`, `getInvoices()`.
- Repository returning a different model than its name implies.

**Enforcement:** Code review; repository class name and injected model must match.  
**References:** [03-data-model-standards](03-data-model-standards.md).

---

### BE-REQ-005: Authorization in controllers; Services receive authorized context

**Rule:** Authorization (Policies/Gates) MUST be invoked in the Controller (or FormRequest). Services receive already-authorized context (e.g. user, tenant) and must not perform authorization checks themselves.

**Rationale:** Single place for “who can do what”; services stay domain-focused.

**Allowed:**

- FormRequest `authorize()` or Controller calling `$this->authorize('update', $order)` before calling Service.
- Service method signature: `create(array $data, User $user)`.

**Anti-examples (forbidden):**

- Service calling `Gate::allows()` or `$user->can()` to decide business flow.
- Controller calling Service without prior authorization when the action is restricted.

**Enforcement:** Code review.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

### BE-REQ-006: No hardcoded domain literals

**Rule:** Domain literals (status strings, type codes, etc.) MUST NOT be hardcoded in business logic. Use Enums or Constants (preferably in Core or the owning module).

**Rationale:** Avoids typos and magic strings; single source of truth.

**Allowed:**

- `OrderStatus::Pending`, `CacheKeys::USER_PREFIX`, config or enum-backed values.

**Anti-examples (forbidden):**

- `if ($status === 'pending')`, `'order_created'` as string in multiple places.

**Enforcement:** Code review; grep for quoted strings that represent domain states.  
**References:** [01-module-boundaries](01-module-boundaries.md).

---

### BE-REQ-007: Jobs/Commands orchestrate Services; Listeners side-effects only

**Rule:** Jobs and Commands MUST orchestrate Services (call service methods). They must not contain business logic. Listeners MUST perform side-effects only (logging, notifications, cache invalidation), not core workflow decisions.

**Rationale:** Reusable business logic lives in Services; queue/console are entry points only.

**Allowed:**

- Job: `$this->orderService->processAsync($payload);`
- Listener: send email, write audit log, invalidate cache.

**Anti-examples (forbidden):**

- Job containing `Order::query()->where(...)->update(...)` and branching logic.
- Listener that creates or updates the main aggregate (that belongs in a Service called from the event dispatcher or from a Job).

**Enforcement:** Code review.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

## Forbidden Patterns (summary)

- Controller: `DB::*`, `Model::query()`, `Model::where()`, business if/else, transaction.
- FormRequest: business logic, repository calls.
- Service: authorization checks (Gate/user->can), direct HTTP calls (use a gateway/port in Core).
- Repository: transaction boundaries, orchestration of multiple aggregates.
- Model: business orchestration, HTTP, authorization.

---

## Enforcement

- **PR:** Checklist aligned with [06-code-review-checklist](06-code-review-checklist.md).
- **CI:** Optional: custom rules or PHPStan level to flag Controller/FormRequest DB usage.
- **References:** [03-data-model-standards](03-data-model-standards.md), [docs/reference/01-request-lifecycle](../reference/01-request-lifecycle.md).
