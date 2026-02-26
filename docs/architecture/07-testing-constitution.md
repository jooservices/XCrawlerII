# 07 - Testing Constitution

## Backend Test Base Class
Rule `07-TST-001`:
All backend tests MUST extend `Modules/Core/tests/TestCase.php`.

Rationale:
Shared bootstrap and helper setup must stay consistent.

Allowed:
```php
final class LoginFeatureTest extends \Modules\Core\tests\TestCase {}
```

Forbidden:
```php
final class LoginFeatureTest extends \Tests\TestCase {}
```

Verification:
- `rg "extends \\Tests\\TestCase" Modules/*/tests` returns no matches.

## Factory + Faker Requirement
Rule `07-TST-002`:
Tests MUST create data with factories and Faker.

Rationale:
Improves realism and reduces brittle fixtures.

Allowed:
```php
$user = User::factory()->create();
```

Forbidden:
```php
$user = User::create(['email' => 'a@b.com']);
```

Verification:
- Feature/unit tests prefer factory APIs.

## No Placeholder Tests
Rule `07-TST-003`:
Placeholder/incomplete tests are forbidden.

Rationale:
False confidence from empty assertions is unacceptable.

Allowed:
```php
$this->postJson('/api/v1/auth/login', [])->assertStatus(422);
```

Forbidden:
```php
it('logs in', function () { $this->assertTrue(true); });
```

Verification:
- Grep for placeholder assertions/skips.

## Correct Classification
Rule `07-TST-004`:
Feature tests validate end-to-end flow; unit tests validate isolated logic.

Rationale:
Misclassified tests hide integration failures.

Allowed:
```text
tests/Feature/Auth/LoginFeatureTest.php
tests/Unit/Auth/PasswordHasherTest.php
```

Forbidden:
```text
tests/Unit/Auth/LoginControllerTest.php
```

Verification:
- Unit tests avoid HTTP stack unless explicitly integration-focused.

## Boundary Mocking Policy
Rule `07-TST-005`:
Feature tests mock only true third-party boundaries.

Rationale:
Internal flow must remain covered.

Allowed:
```php
$gateway = Mockery::mock(SmsGatewayPort::class);
```

Forbidden:
```php
$this->mock(OrderService::class);
```

Verification:
- Internal layers are real in feature tests.

## Coverage Constitution
Rule `07-TST-006`:
All classes in feature scope, including FormRequest classes, MUST have tests.

Rationale:
Validation rules and edge behavior regress first.

Allowed:
```text
tests/FormRequest/Auth/LoginRequestTest.php
```

Forbidden:
```text
# FormRequest added without tests
```

Verification:
- Feature checklist confirms FormRequest test exists.

## Mandatory Test Categories
Rule `07-TST-007`:
Every feature includes happy, unhappy, weird, security/exploit, and edge scenarios.

Rationale:
Production failures usually occur outside happy path.

Allowed:
```text
- valid login
- wrong password
- unicode input edge
- brute-force lock
- concurrent retry
```

Forbidden:
```text
- happy path only
```

Verification:
- Test plan maps each required category.

## Frontend Testing Rules
Rule `07-TST-008`:
FE tests MUST be separated from FE code, with Vitest + Vue Test Utils for unit/component tests and Playwright for E2E critical flows.

Rationale:
UI regressions need fast checks and user-flow coverage.

Allowed:
```text
Modules/Auth/tests/Frontend/unit/LoginForm.spec.ts
Modules/Auth/tests/Frontend/e2e/login-flow.spec.ts
```

Forbidden:
```text
resources/js/pages/LoginPage.spec.ts
```

Verification:
- Critical flow list has matching E2E specs.
- `package.json` includes `vitest`, `@vue/test-utils`, and `playwright`.

## Backend Test Framework Rule
Rule `07-TST-009`:
Backend tests MUST use PHPUnit exclusively; Pest syntax and runner are forbidden.

Rationale:
Single backend test framework avoids mixed conventions and execution drift.

Allowed:
```php
final class LoginFeatureTest extends \Modules\Core\tests\TestCase
{
    public function test_login_requires_email(): void {}
}
```

Forbidden:
```php
it('logs in user', function () {
    // Pest syntax is forbidden
});
```

Verification:
- `composer.json` must not include `pestphp/pest`.
- Grep for Pest syntax in backend tests:
  - lines starting with `it(` or `test(` under `Modules/*/tests`.

## Templates
Backend feature test template:
```php
final class CreateOrderFeatureTest extends \Modules\Core\tests\TestCase
{
    public function test_create_order_happy_path(): void {}
    public function test_create_order_validation_error(): void {}
    public function test_create_order_security_forbidden(): void {}
}
```

Frontend unit template:
```ts
describe('LoginForm', () => {
  it('submits valid credentials', async () => {});
  it('shows validation errors from API contract', async () => {});
});
```
