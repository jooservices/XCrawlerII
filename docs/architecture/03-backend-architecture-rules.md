# 03 - Backend Architecture Rules

## Layering
Rule `03-BE-001`:
Execution flow MUST be `Controller -> FormRequest -> Service/UseCase -> Repository -> Model`.

Rationale:
Separates transport, validation, orchestration, persistence, and data shape concerns.

Allowed:
```php
public function actionLogin(LoginRequest $request, AuthService $service): RedirectResponse {
    $service->login($request->validated('email'), $request->validated('password'));
    return redirect()->route('render.dashboard.index');
}
```

Forbidden:
```php
public function actionLogin(Request $request): RedirectResponse {
    $user = User::query()->where('email', $request->email)->first();
}
```

Verification:
- Controllers contain no query builder or domain branching.

## Thin Controller
Rule `03-BE-002`:
Controllers MUST NOT contain business logic or direct DB queries (`DB::`, `Model::query()`, joins, transactions).

Rationale:
Controller complexity causes duplicate rules and test gaps.

Allowed:
```php
return $this->authService->logout($request->user());
```

Forbidden:
```php
DB::transaction(function () { /* domain workflow */ });
```

Verification:
- Static grep in controllers for `DB::|::query\(` returns no matches.

## Repository Strictness
Rule `03-BE-003`:
Maintain strict `1 Model <-> 1 Repository` ownership.

Rationale:
Clear ownership simplifies consistency and testability.

Allowed:
```php
class UserRepository { public function findByEmail(string $email): ?User {} }
```

Forbidden:
```php
class SharedRepository { public function users() {} public function orders() {} }
```

Verification:
- Repository class name maps to single model aggregate.

## Transactions
Rule `03-BE-004`:
Service layer owns transaction boundaries; repositories perform persistence operations only.

Rationale:
Cross-repository orchestration requires service-level transaction control.

Allowed:
```php
$this->db->transaction(function () use ($payload) {
    $order = $this->orderRepository->create($payload);
    $this->inventoryRepository->reserve($order);
});
```

Forbidden:
```php
class OrderRepository { public function createOrderAndReserveStock() { DB::transaction(...); } }
```

Verification:
- Transaction APIs exist in services/use-cases, not repositories/controllers.

## Jobs, Commands, Events, Listeners
Rule `03-BE-005`:
Jobs/commands are orchestration-only and MUST call services. Events represent facts that already happened. Listeners contain side effects only.

Rationale:
Prevents hidden workflow branching in async/event layers.

Allowed:
```php
class SendInvoiceJob { public function handle(BillingService $service): void { $service->sendInvoice($this->invoiceId); } }
```

Forbidden:
```php
class InvoicePaidListener { public function handle(InvoicePaid $event): void { /* core workflow here */ } }
```

Verification:
- Listener code restricted to side effects.
- Core workflow logic stays in services.

## Error Schema
Rule `03-BE-006`:
All API errors MUST follow one JSON schema.

Rationale:
Clients need stable parsing for failure handling.

Allowed:
```json
{
  "error": {
    "code": "VALIDATION_FAILED",
    "message": "Validation failed",
    "details": [{"field": "email", "reason": "required"}],
    "trace_id": "a1b2c3"
  }
}
```

Forbidden:
```json
{"msg":"fail"}
```

Verification:
- Exception renderer normalizes all errors.
- Contract tests assert schema keys.

## Authorization
Rule `03-BE-007`:
Use Laravel Policies/Gates for authorization decisions; services receive authorized context, not permission logic.

Rationale:
Centralized authorization avoids inconsistent access checks.

Allowed:
```php
$this->authorize('update', $invoice);
$service->updateInvoice($invoice, $data);
```

Forbidden:
```php
if ($user->role === 'admin') { /* service permission branch */ }
```

Verification:
- Controllers call `authorize` or middleware.

## Laravel Conventions
Rule `03-BE-008`:
Use route model binding, API Resources/DTO mapping, eager loading to avoid N+1, guarded mass assignment, and centralized exception rendering.

Rationale:
These conventions reduce bugs and enforce predictable behavior.

Allowed:
```php
public function show(User $user): JsonResponse {
    $user->loadMissing(['roles']);
    return response()->json(UserResource::make($user));
}
```

Forbidden:
```php
User::all()->each(fn ($u) => $u->roles->count()); // N+1
```

Verification:
- N+1 checks in tests/profiling.
- `$fillable`/`$guarded` explicitly defined.

## Hardcode Ban
Rule `03-BE-009`:
Domain literals in services/queries/validation/factories/tests are forbidden; use enums/constants.

Rationale:
Literals hide rules and break maintainability.

Allowed:
```php
enum UserStatus: string { case ACTIVE = 'active'; }
if ($user->status === UserStatus::ACTIVE) {}
```

Forbidden:
```php
if ($user->status === 'active') {}
```

Verification:
- Grep for known domain strings in logic directories.

## Comment Policy
Rule `03-BE-010`:
Comments MUST explain WHY, trade-offs, invariants, or decision links. WHAT-comments are forbidden.

Rationale:
Noise comments decay quickly and obscure important intent.

Allowed:
```php
// Why: idempotency lock prevents duplicate charge during client retry storms.
```

Forbidden:
```php
// Get user from repository.
$user = $this->users->findById($id);
```

Verification:
- Review comments for rationale content.

## DI Policy
Rule `03-BE-011`:
Constructor injection MUST stay lean. Dependencies used across the whole class belong in constructor; rare dependencies use method injection or controlled resolve. Constructor deps >5 require explicit justification or refactor.

Rationale:
Over-injected constructors increase coupling and reduce maintainability.

Allowed:
```php
public function __construct(private UserRepository $users, private AuditLogger $logger) {}
```

Forbidden:
```php
public function __construct(private A $a, private B $b, private C $c, private D $d, private E $e, private F $f) {}
```

Verification:
- Constructor dependency count >5 requires justification.

## Early Return / Guard Clauses
Rule `03-BE-012`:
Use guard clauses and early returns to reduce nesting; target nesting depth <= 3.

Rationale:
Flattened control flow improves readability and failure-path safety.

Allowed:
```php
if (! $order->isPayable()) { return; }
if ($order->isExpired()) { throw new DomainException('ORDER_EXPIRED'); }
$this->paymentGateway->charge($order);
```

Forbidden:
```php
if ($order->isPayable()) {
  if (! $order->isExpired()) {
    if ($order->hasCard()) {
      if ($order->isAuthorized()) {
        $this->paymentGateway->charge($order);
      }
    }
  }
}
```

Verification:
- Review changed methods for guard-clause opportunities and excessive nesting.
