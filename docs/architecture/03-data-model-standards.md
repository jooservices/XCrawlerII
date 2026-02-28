# 03 - Data Model Standards

## Purpose

Define how MySQL (Eloquent) and MongoDB models are named, where they live, and how they declare table/collection and fillable attributes so that the persistence layer is consistent and predictable.

## Scope

- All Eloquent and MongoDB models in `app/Models/` and `Modules/*/app/Models/` (including `Modules/*/app/Models/MongoDb/`).
- Table and collection naming, fillable, timestamps.

## Non-goals

- Migration or schema change workflow (see project/DB docs)
- Repository interface design (see [02-backend-layering](02-backend-layering.md))

## Definitions

| Term              | Meaning                                                                                                                               |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------- |
| **SQL model**     | Eloquent model using a SQL connection (e.g. MariaDB); typically under `app/Models/` or `Modules/<Module>/app/Models/`.                |
| **MongoDB model** | Model using MongoDB connection; MUST extend `\Modules\Core\app\Models\MongoDb` and live under `Modules/<Module>/app/Models/MongoDb/`. |
| **TABLE**         | PHP constant for SQL table name (singular concept, table name often plural).                                                          |
| **COLLECTION**    | PHP constant for MongoDB collection name.                                                                                             |

---

## Naming Convention

- **Model class name** MUST match the singular form of the table or collection name.
- **Table/collection name** is typically plural (e.g. `client_logs`, `xcrawler_logs`).
- **Model name** is singular: `ClientLog`, `XCrawlerLog`.

Examples:

| Table/Collection | Model class   |
| ---------------- | ------------- |
| `client_logs`    | `ClientLog`   |
| `xcrawler_logs`  | `XCrawlerLog` |
| `configs`        | `Config`      |
| `users`          | `User`        |

---

## Rules

### DATA-MOD-001: Model name matches singular table/collection name

**Rule:** The model class name MUST match the singular form of the table (SQL) or collection (MongoDB) name. If the table/collection is `client_logs`, the model MUST be `ClientLog`; if `xcrawler_logs`, the model MUST be `XCrawlerLog`.

**Rationale:** Predictable mapping between code and database; avoids confusion.

**Allowed:**

- Table `client_logs` → Model `ClientLog`.
- Collection `xcrawler_logs` → Model `XCrawlerLog`.

**Anti-examples (forbidden):**

- Table `client_logs` → Model `ClientLogs` (plural class name).
- Table `users` → Model `UserModel` (redundant suffix unless project standard).

**Enforcement:** Code review; naming checklist.  
**References:** [02-backend-layering](02-backend-layering.md).

---

### DATA-MOD-002: TABLE (SQL) or COLLECTION (Mongo) constant required

**Rule:** Every model MUST define a typed constant using PHP 8.3 typed constants syntax: `public const string TABLE` (SQL) or `public const string COLLECTION` (MongoDB), and MUST assign it to `protected $table = self::TABLE` or `self::COLLECTION`.

**Rationale:** Single source of truth for table/collection name; refactor-safe.

**Allowed:** See MySQL and Mongo examples below.

**Anti-examples (forbidden):**

- `protected $table = 'client_logs';` without a constant.
- MongoDB model without `COLLECTION` constant.

**Enforcement:** Code review; grep for `$table\s*=` and ensure constant exists.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

### DATA-MOD-003: MongoDB models extend Core base and live in MongoDb folder

**Rule:** Every MongoDB-backed model MUST extend `\Modules\Core\app\Models\MongoDb` and MUST live under `Modules/<Module>/app/Models/MongoDb/`. They must NOT extend `MongoDB\Laravel\Eloquent\Model` directly and must NOT be placed in the generic `Models/` folder.

**Rationale:** Consistent connection and behavior; clear separation from SQL models.

**Allowed:**

- `Modules\Core\Models\MongoDb\ClientLog` in file `Modules/Core/app/Models/MongoDb/ClientLog.php`.
- `Modules\Crawler\Models\MongoDb\XcrawlerLog` in file `Modules/Crawler/app/Models/MongoDb/XcrawlerLog.php`.

**Anti-examples (forbidden):**

- `Modules\Core\Models\ClientLog` extending `MongoDB\Laravel\Eloquent\Model`.
- MongoDB model in `Modules/Crawler/app/Models/` (no `MongoDb` subfolder).

**Enforcement:** Code review; path and extends check.  
**References:** [01-module-boundaries](01-module-boundaries-and-dependencies.md).

---

### DATA-MOD-004: Explicit fillable; no $guarded = []

**Rule:** Every model MUST define `$fillable` explicitly. Using `$guarded = []` (allow all) is FORBIDDEN.

**Rationale:** Mass assignment safety; explicit allow-list prevents accidental exposure.

**Allowed:**

- `protected $fillable = ['name', 'email', 'status'];`

**Anti-examples (forbidden):**

- `protected $guarded = [];`
- No `$fillable` and no `$guarded` (ambiguous).

**Enforcement:** Code review; grep for `$guarded\s*=\s*\[\]`.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

### DATA-MOD-005: Timestamps always enabled; not in fillable

**Rule:** Timestamps MUST be enabled (`created_at` / `updated_at`). Do NOT set `$timestamps = false` unless an explicit exception is documented (e.g. read-only log table). Do NOT include `created_at` or `updated_at` in `$fillable`.

**Rationale:** Consistent audit trail; framework manages these columns.

**Allowed:**

- Default timestamps (no `$timestamps = false`); `$fillable` lists only business attributes.

**Anti-examples (forbidden):**

- `$timestamps = false` without ADR or exception doc.
- `$fillable = [..., 'created_at', 'updated_at'];`

**Enforcement:** Code review.  
**References:** [06-code-review-checklist](06-code-review-checklist.md).

---

## MySQL Model Example

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final class User extends Model
{
    use HasFactory;

    public const string TABLE = 'users';

    protected $table = self::TABLE;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
```

- Model name `User` ↔ table `users` (singular ↔ plural).
- `TABLE` constant and `$table = self::TABLE`.
- Explicit `$fillable`; no `created_at`/`updated_at` in fillable.
- Timestamps left enabled (default).

---

## MongoDB Model Example (correct folder and base class)

```php
<?php

declare(strict_types=1);

namespace Modules\Core\Models\MongoDb;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Models\MongoDb;

final class ClientLog extends MongoDb
{
    use HasFactory;

    public const string COLLECTION = 'client_logs';

    protected $table = self::COLLECTION;

    protected $fillable = [
        'ts',
        'site',
        'method',
        'path',
        'url',
        'status',
        'ok',
        'duration_ms',
        'attempt',
        'retries',
        'max_attempts',
        'request',
        'response',
        'cache',
        'error',
        'correlation_id',
        'trace_id',
        'tags',
        'task_id',
        'job_id',
    ];
}
```

- File path: `Modules/Core/app/Models/MongoDb/ClientLog.php`.
- Extends `Modules\Core\Models\MongoDb` (project base), not `MongoDB\Laravel\Eloquent\Model`.
- `COLLECTION` constant and `$table = self::COLLECTION`.
- Explicit `$fillable`; no timestamps in fillable.
- Collection `client_logs` → Model `ClientLog`.

For a **feature module** MongoDB model, same rules: extend `\Modules\Core\app\Models\MongoDb`, place in `Modules/<Feature>/app/Models/MongoDb/<ModelName>.php`, define `COLLECTION` and `$fillable`.

---

## Enforcement

- **PR:** Checklist in [06-code-review-checklist](06-code-review-checklist.md).
- **CI:** Optional: PHPStan or custom rule for TABLE/COLLECTION and fillable.
- **References:** [02-backend-layering](02-backend-layering.md), [docs/reference/00-system-overview](../reference/00-system-overview.md).
