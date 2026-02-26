# 06 - Database Standards

## Service Baseline Versions
Rule `06-DB-001`:
Use supported baselines: MariaDB `11.8` LTS, MongoDB `8.0` long-term support major, Redis stable channel pinned by major.minor, and Elasticsearch stable pinned by major.minor.

Rationale:
Balance support lifecycle and deterministic operations.

Allowed:
```env
MARIADB_VERSION=11.8
MONGODB_VERSION=8.0
REDIS_VERSION=8.2
ELASTICSEARCH_VERSION=9.1
```

Forbidden:
```env
MARIADB_VERSION=latest
```

Verification:
- Runtime manifests pin major.minor.

## Storage Role Split
Rule `06-DB-002`:
MariaDB is relational source of truth; MongoDB for document workloads; Redis for cache/locks/idempotency; Elasticsearch for search indexing.

Rationale:
Prevents overlapping sources of truth.

Allowed:
```text
Orders authoritative in MariaDB, indexed copy in Elasticsearch.
```

Forbidden:
```text
Primary order status in Redis only.
```

Verification:
- Data ownership documented per module.

## MariaDB Schema Conventions
Rule `06-DB-003`:
Use snake_case names, explicit FK constraints, lookup indexes, and timestamps.

Rationale:
Consistent relational design and migration safety.

Allowed:
```php
Schema::create('orders', function (Blueprint $table) {
  $table->id();
  $table->foreignId('user_id')->constrained()->cascadeOnDelete();
  $table->string('status', 32);
  $table->timestamps();
});
```

Forbidden:
```php
Schema::create('OrderTable', function (Blueprint $table) { $table->string('UserID'); });
```

Verification:
- Migration review checks naming/index/FK conventions.

## MongoDB Collection Conventions
Rule `06-DB-004`:
Collection names are snake_case plural; documents include `created_at`, `updated_at`, and `schema_version` when shape may evolve.

Rationale:
Schema evolution must be trackable.

Allowed:
```json
{"_id":"...","schema_version":1,"created_at":"...","updated_at":"..."}
```

Forbidden:
```json
{"_id":"...","payload":{}}
```

Verification:
- Validators/models enforce required metadata.

## Redis Key Conventions
Rule `06-DB-005`:
Redis keys MUST follow `{module}:{entity}:{purpose}:{id}` with TTL for non-permanent keys.

Rationale:
Avoid key collisions and unbounded memory growth.

Allowed:
```text
auth:token:blacklist:123 (ttl=3600)
billing:payment:idempotency:6f0f3d8b (ttl=86400)
```

Forbidden:
```text
cache_1
```

Verification:
- Key namespace pattern and TTL checks pass.

## Elasticsearch Index Conventions
Rule `06-DB-006`:
Use `{domain}-v{major}` naming with read/write aliases; mapping changes require reindex flow.

Rationale:
Safe schema evolution.

Allowed:
```text
orders-v1 (aliases: orders-read, orders-write)
```

Forbidden:
```text
orders-index-final-final
```

Verification:
- Alias and reindex process documented.

## Migrations/Seeders/Factories
Rule `06-DB-007`:
Every new persistent model requires migration + factory (Faker) + meaningful states.

Rationale:
Reliable tests and reproducible environments.

Allowed:
```php
UserFactory::new()->state(['status' => UserStatus::ACTIVE->value]);
```

Forbidden:
```php
// no factory, hard-coded test arrays only
```

Verification:
- New model PR includes migration and factory states.
