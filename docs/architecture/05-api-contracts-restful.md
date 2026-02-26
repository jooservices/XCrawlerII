# 05 - API Contracts RESTful

## Versioning
Rule `05-API-001`:
All API endpoints MUST be under `/api/v1` and namespaced as `api.v1.*`.

Rationale:
Versioning protects clients from breaking changes.

Allowed:
```http
GET /api/v1/users
```

Forbidden:
```http
GET /api/users
```

Verification:
- `route:list` path and names follow `api/v1` + `api.v1`.

## Resource RESTfulness
Rule `05-API-002`:
Resource APIs MUST be RESTful with plural nouns and standard methods.

Rationale:
Stable semantics and tooling compatibility.

Allowed:
```http
GET /api/v1/orders
POST /api/v1/orders
GET /api/v1/orders/{order}
PUT /api/v1/orders/{order}
DELETE /api/v1/orders/{order}
```

Forbidden:
```http
POST /api/v1/getOrders
```

Verification:
- No verb-in-path for resource routes.

## Required Status Codes
| Operation | Required Status |
|---|---|
| GET (collection/item) | 200 OK |
| POST (create) | 201 Created (include `Location` header when applicable) |
| PUT/PATCH (update) | 200 OK |
| DELETE (delete) | 204 No Content (no response body) |
| Validation error | 422 Unprocessable Entity |
| Unauthenticated | 401 Unauthorized |
| Unauthorized | 403 Forbidden |
| Not found | 404 Not Found |
| Conflict | 409 Conflict |
| Internal error | 500 Internal Server Error |

Verification:
- Controller/API feature tests assert exact status code per operation.
- Delete assertions verify empty body for `204 No Content`.

## Success Response Schema
Rule `05-API-003`:
Success responses MUST use resource object or collection envelope with metadata.

Rationale:
Predictable parsing for clients.

Allowed:
```json
{"data": [{"id": 1}], "meta": {"page": 1, "per_page": 20, "total": 100}}
```

Forbidden:
```json
[{"id": 1}]
```

Verification:
- Contract tests assert `data` and `meta` shape for collections.

## Error Response Schema
Rule `05-API-004`:
Error responses MUST match unified error schema.

Rationale:
Client-side error handling remains deterministic.

Allowed:
```json
{"error": {"code": "RESOURCE_NOT_FOUND", "message": "Order not found", "details": [], "trace_id": "trace-123"}}
```

Forbidden:
```json
{"errorMessage":"Order missing"}
```

Verification:
- Automated schema validation in API tests.

## Pagination/Filter/Sort
Rule `05-API-005`:
Use query conventions: `page`, `per_page`, `filter[...]`, `sort` (`-` prefix for desc).

Rationale:
Uniform query contract for FE and external clients.

Allowed:
```http
GET /api/v1/orders?page=2&per_page=50&filter[status]=pending&sort=-created_at
```

Forbidden:
```http
GET /api/v1/orders?statusFilter=pending&orderBy=created_at_desc
```

Verification:
- Request validation enforces allowed query keys.

## Idempotency for Mutations
Rule `05-API-006`:
Mutation endpoints SHOULD support `Idempotency-Key` for non-idempotent operations.

Rationale:
Prevents duplicate processing during retries.

Allowed:
```http
POST /api/v1/payments
Idempotency-Key: 6f0f3d8b-...
```

Forbidden:
```http
POST /api/v1/payments
# duplicate request creates duplicate charges
```

Verification:
- Idempotency store checked before side effects.
- Tests cover duplicate key replay.

## Auth Route Exception
Rule `05-API-007`:
Auth action endpoints may be non-resource and MUST be documented in Exception Registry as `AUTH_ACTION_ENDPOINT`. This rule is the canonical source for Auth non-resource API action governance.

Rationale:
Auth flows are actions, not CRUD resources.

Allowed:
```http
POST /api/v1/auth/login
POST /api/v1/auth/logout
```

Forbidden:
```http
POST /api/v1/auth/login
# no exception record
```

Verification:
- Registry references each non-resource auth route.
