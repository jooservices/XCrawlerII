# 02 - Routing and Controllers Standard

## Web Route Grouping
Rule `02-ROU-001`:
Each module MUST define both `render.*` and `action.*` route groups in a single `routes/web.php`.

Rationale:
Single-file grouping keeps web orchestration discoverable and consistent.

Allowed:
```php
Route::prefix('render')->name('render.auth.')->group(function () {
    Route::get('/login', [AuthController::class, 'renderLogin'])->name('login');
});

Route::prefix('action')->name('action.auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'actionLogin'])->name('login');
    Route::post('/logout', [AuthController::class, 'actionLogout'])->name('logout');
});
```

Forbidden:
```php
// split render/action routes across multiple files
```

Verification:
- Every module has exactly one `routes/web.php` containing both groups.

## API v1 Route Grouping
Rule `02-ROU-002`:
Each module MUST define `routes/api_v1.php` using prefix `/api/v1` and names `api.v1.{group}.*`.

Rationale:
Versioned API contracts avoid breaking clients.

Allowed:
```php
Route::prefix('api/v1')->name('api.v1.auth.')->group(function () {
    Route::post('/login', [ApiAuthController::class, 'login'])->name('login');
    Route::post('/logout', [ApiAuthController::class, 'logout'])->name('logout');
});
```

Forbidden:
```php
Route::prefix('api')->name('api.auth.')->group(function () {});
```

Verification:
- `route:list` names start with `api.v1.` and paths start with `/api/v1`.

## Controller Naming and Method Convention
Rule `02-ROU-003`:
Web controllers use `Controllers\{Group}Controller` with `renderX`/`actionX`. API controllers use `Controllers\Api{Group}Controller` with plain REST/action names.

Rationale:
Method semantics are obvious from route context.

Allowed:
```php
class AuthController {
    public function renderLogin() {}
    public function actionLogin() {}
    public function actionLogout() {}
}

class ApiAuthController {
    public function login() {}
    public function logout() {}
}
```

Forbidden:
```php
class AuthController { public function loginAction() {} }
```

Verification:
- Web methods use `render`/`action` prefixes.
- API resources use RESTful method names.

## RESTful Resource Endpoints
Rule `02-ROU-004`:
API data resources MUST be RESTful: plural nouns, standard verbs, correct status codes, structured response DTO/Resource.

Rationale:
Consistent API semantics reduce client complexity.

Allowed:
```php
Route::get('/users', [ApiUserController::class, 'index'])->name('index');
Route::post('/users', [ApiUserController::class, 'store'])->name('store');
Route::get('/users/{user}', [ApiUserController::class, 'show'])->name('show');
Route::put('/users/{user}', [ApiUserController::class, 'update'])->name('update');
Route::delete('/users/{user}', [ApiUserController::class, 'destroy'])->name('destroy');
```

Forbidden:
```php
Route::post('/getUsers', [ApiUserController::class, 'getUsers']);
```

Verification:
- No verb-in-path patterns for data resources.
- Controller returns expected REST status codes.

## Auth API Exception
Rule `02-ROU-005`:
Auth endpoints under API may use non-resource actions (`login`, `logout`, `refresh`), with canonical governance defined by `05-API-007` in `05-api-contracts-restful.md`.

Rationale:
Auth flows are action-based and not resource CRUD.

Allowed:
```php
Route::post('/api/v1/auth/login', [ApiAuthController::class, 'login']);
```

Forbidden:
```php
Route::post('/api/v1/profile/changePassword', ...);
```

Verification:
- Reuse verification defined in `05-API-007` (single source of truth).

## Default URL Prefixes
Rule `02-ROU-006`:
Default URL prefixes MUST be `/render/...`, `/action/...`, and `/api/v1/...` for render, action, and API v1 routes respectively.

Rationale:
Explicit prefix conventions keep endpoint discovery and contract consistency deterministic.

Allowed:
```text
/render/auth/login
/action/auth/login
/api/v1/auth/login
```

Forbidden:
```text
/view/auth/login
/do/auth/login
/api/auth/login
```

Verification:
- `route:list` paths show `/render`, `/action`, and `/api/v1` prefixes by route group.
