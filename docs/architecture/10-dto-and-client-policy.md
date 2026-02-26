# 10 - DTO and Client Policy

## DTO MUST Rules
Rule `10-DTO-001`:
Use DTOs only for boundary I/O: HTTP input/output contracts, event payloads, integration request/response, and cross-module public contracts.

Rationale:
DTOs are contract tools, not internal wrappers.

Allowed:
```php
final readonly class LoginRequestDto extends Data
{
    public function __construct(public string $email, public string $password) {}
}
```

Forbidden:
```php
final readonly class CalculateFeeInputDto extends Data
{
    public function __construct(public int $a, public int $b) {}
}
```

Verification:
- DTO usage appears at module boundaries.

## DTO NOT Rules
Rule `10-DTO-002`:
Do NOT use DTOs for internal method-to-method passing, 1:1 model clones, or trivial filter bags.

Rationale:
DTO spam adds ceremony without value.

Allowed:
```php
public function calculateFee(int $amount, Currency $currency): int {}
```

Forbidden:
```php
public function calculateFee(CalculateFeeDto $dto): int {}
```

Verification:
- DTO classes are justified by boundary concerns.

## DTO Budget
Rule `10-DTO-003`:
Default DTO budget per feature is 2-4 DTOs unless an exception is approved. Budget counts ALL DTOs introduced by the feature, including DTOs placed in Core.

Rationale:
Budget forces deliberate contract design.

Allowed:
```text
Auth feature DTOs: LoginRequestDto, LoginResultDto, AuthTokenRefreshDto
```

Forbidden:
```text
Auth feature DTOs: 14 pass-through DTO classes
```

Verification:
- Feature spec lists DTO count and value provided by each DTO.

## DTO Value Requirement
Rule `10-DTO-004`:
Each DTO must provide at least one real value: normalization/casting, invariants/value object usage, external schema mapping, or stable public contract.

Rationale:
Ensures DTO existence is justified.

Allowed:
```php
final readonly class ExternalUserDto extends Data
{
    public function __construct(public string $externalId, public Email $email) {}
}
```

Forbidden:
```php
final readonly class UserCopyDto extends Data
{
    public function __construct(public int $id, public string $name) {}
}
```

Verification:
- DTO checklist in PR states explicit value category.

## `jooservices/dto` Usage
Rule `10-DTO-005`:
Use `jooservices/dto` for boundary DTOs only.

Rationale:
Package intent is contract stabilization.

Allowed:
```php
use JooServices\Dto\Data;
final readonly class CreateOrderRequestDto extends Data {}
```

Forbidden:
```php
// Internal service calls wrapped in DTOs
```

Verification:
- DTO package imports appear in boundary layers.

## `jooservices/client` Usage
Rule `10-DTO-006`:
`jooservices/client` MUST be consumed behind Core-defined adapter interfaces; domain services call interfaces only.

Rationale:
Keeps domain testable and vendor-independent.

Allowed:
```php
interface FraudCheckPort { public function check(FraudCheckRequestDto $dto): FraudCheckResultDto; }
```

Forbidden:
```php
final class PaymentService { public function __construct(private JooClient $client) {} }
```

Verification:
- Domain services inject port interfaces, not concrete client.

## Example Set
Request DTO example:
```php
final readonly class CreateTicketRequestDto extends Data
{
    public function __construct(public string $title, public string $description) {}
}
```

Result DTO example:
```php
final readonly class CreateTicketResultDto extends Data
{
    public function __construct(public int $ticketId, public string $status) {}
}
```

Integration DTO example:
```php
final readonly class FraudCheckResultDto extends Data
{
    public function __construct(public bool $isRisky, public string $riskLevel) {}
}
```
