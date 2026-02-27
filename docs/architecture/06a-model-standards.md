# 06a - Model Standards

This document defines the strict standard for all persistence layer Models (MySQL, MongoDB, and future DBs).

## 1. Model Naming Convention

Rule `06A-MOD-001`:
Model Naming MUST match the table/collection name in a singular form.
If the table/collection naming is irregular or legacy, the Model MUST explicitly declare the table/collection mapping and document the exception.
Naming still MUST be singular class naming unless there is an explicit architectural exception section.

Rationale:
Predictable mapping between code models and database storage.

Allowed:

- Table `client_logs` -> Model `ClientLog`

Forbidden:

- Table `client_logs` -> Model `ClientLogs`

Verification:

- The model class name is the exact singular form of the table or collection name.

## 2. Base Class Extension (MongoDB)

Rule `06A-MOD-002`:
Any MongoDB-backed model MUST extend the shared base class `\Modules\Core\app\Models\MongoDb`.
This applies to 100% of MongoDB models. No exceptions.

Rationale:
Ensures all MongoDB models inherit core behaviors, connection resolutions, and necessary trait implementations.

Allowed:

```php
namespace Modules\Core\app\Models\MongoDb;

use Modules\Core\app\Models\MongoDb as BaseMongoDb;

class ClientLog extends BaseMongoDb
{
    // ...
}
```

Forbidden:

```php
// Not extending the shared base
use MongoDB\Laravel\Eloquent\Model;

class ClientLog extends Model
{
    // ...
}
```

Verification:

- Model `extends \Modules\Core\app\Models\MongoDb`.

## 3. Directory Location (MongoDB Models)

Rule `06A-MOD-003`:
Any MongoDB-backed model MUST be located in a dedicated directory under its module: `Modules/{ModuleName}/app/Models/MongoDb/`.
MongoDB models MUST NOT be placed directly under the `Models/` root or elsewhere.

Rationale:
Clear separation of SQL and NoSQL models to avoid query and trait confusion.

Allowed path:
`Modules/Core/app/Models/MongoDb/ClientLog.php`

Forbidden path:
`Modules/Core/app/Models/ClientLog.php` (if it is a MongoDB model)

Verification:

- Path matches `app/Models/MongoDb/`.

## 4. Backend Categorization

Rule `06A-MOD-004`:
100% of models MUST be explicitly categorized by backend.
Every model MUST clearly be a “SQL model” or “MongoDB model” (or future backend) in a consistent documented way (via directory placement and base class).
No ambiguous “generic” models without backend clarity.
The canonical approach for connection/driver usage MUST be inherited via the base class.

Rationale:
Ensures developers immediately know which database engine the model operations will target.

Verification:

- SQL models are placed under `app/Models/` directly and extend standard Eloquent Model.
- MongoDB models are placed under `app/Models/MongoDb/` and extend `Modules\Core\app\Models\MongoDb`.

## 5. Mass Assignment Policy

Rule `06A-MOD-005`:
Models MUST use `$fillable` as an explicit allow-list.
Models MUST NOT use `protected $guarded = [];` (never “open guard”).
`$fillable` MUST NOT include system-managed timestamp fields (`created_at`, `updated_at`, etc.).

Rationale:
Security against mass assignment vulnerabilities. Timestamps are enabled by default and should be managed by the persistence layer automatically, not by mass assignment.

Allowed:

```php
protected $fillable = [
    'client_id',
    'status',
    'payload',
];
```

Forbidden:

```php
// Never use open guard
protected $guarded = [];

// Do not include timestamps in fillable
protected $fillable = [
    'client_id',
    'created_at',
    'updated_at',
];
```

Guidance:
Maintain `$fillable` explicitly. DTO mappings should strictly extract only the allowed properties.

Verification:

- Model has `$fillable`.
- Model does not have `$guarded = []`.
- `created_at` / `updated_at` (and backend equivalents) are absent from `$fillable`.

## 6. Timestamps Policy

Rule `06A-MOD-006`:
Models MUST NOT set `public $timestamps = false`.
Timestamps are TRUE by default; therefore most models SHOULD NOT override `$timestamps` at all.
By default, every model MUST have timestamps (SQL: `created_at`, `updated_at`; MongoDB equivalents).

Rationale:
Accountability, debugging, and schema lifecycle visibility rely on accurate timestamps. Both SQL and MongoDB models MUST retain enabled timestamps.

Allowed:

```php
// Leave timestamps enabled by default
```

Forbidden:

```php
public $timestamps = false;
```

Verification:

- `public $timestamps = false;` is absent from all models.

## 7. Table/Collection Constant Policy

Rule `06A-MOD-007`:
All models MUST define the backing table/collection name via a class constant and reference it in the model property.
MUST NOT hardcode table/collection strings directly in `$table` or `$collection`.

Rationale:
Allows other components (e.g., repositories, query builders, migrations) to safely reference the underlying table/collection without duplicate magic strings.

Allowed (SQL):

```php
public const string TABLE = 'client_logs';
protected $table = self::TABLE;
```

Allowed (MongoDB):

```php
public const string COLLECTION = 'configs';
// Assuming the project canonical approach requires binding the Mongo collection to $table:
protected $table = self::COLLECTION;

// Or if it expects $collection:
protected $collection = self::COLLECTION;
```

Forbidden:

```php
protected $table = 'client_logs';
```

Verification:

- `TABLE` or `COLLECTION` constant exists.
- The property `$table` or `$collection` uses the constant.

---

## Reviewer Checklist / DoD for Models

Reviewers MUST verify the following for any new or updated model:

- [ ] **Naming:** Is the model class named as the singular version of its table/collection?
- [ ] **Base Class:** If MongoDB, does it extend `\Modules\Core\app\Models\MongoDb`?
- [ ] **Location:** If MongoDB, is it under `Modules/{ModuleName}/app/Models/MongoDb/`?
- [ ] **Backend Clarity:** Is the backend implicitly clear via location and base class?
- [ ] **Mass Assignment:** Does it explicitly declare `$fillable` (and NOT `$guarded = []`)?
- [ ] **Timestamps in Fillable:** Are timestamps (`created_at` / `updated_at`) excluded from `$fillable`?
- [ ] **Timestamps Enabled:** Is `$timestamps = false` entirely omitted?
- [ ] **Table/Collection Constant:** Is `public const string TABLE` (or `COLLECTION`) defined and used to set the respective property?

## Related Docs

- [00 - Project Structure](00-project-structure.md)
- [01 - Module Boundaries and Dependencies](01-module-boundaries-and-dependencies.md)
- [03 - Backend Architecture Rules](03-backend-architecture-rules.md)
- [06 - Database Standards](06-database-standards.md)
- [07 - Testing Constitution](07-testing-constitution.md)
- [09 - Feature Definition of Done](09-feature-definition-of-done.md)
