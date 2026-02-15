# Controller Skeleton

```php
<?php

declare(strict_types=1);

namespace Modules\JAV\Http\Controllers\Users\Api;

use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Requests\ExampleRequest;
use Modules\JAV\Services\ExampleDomainService;

final class ExampleController
{
    public function __construct(private readonly ExampleDomainService $service)
    {
    }

    /**
     * Return a validated and stable JSON contract.
     *
     * Why: API consumers depend on fixed shape and error semantics.
     */
    public function index(ExampleRequest $request): JsonResponse
    {
        $payload = $this->service->buildPayload($request->validated());

        return response()->json($payload);
    }
}
```

## Pseudo-code (Failure Handling Pattern)

```text
try:
  payload = service.buildPayload(validatedInput)
  return 200(payload)
catch DomainValidationError:
  return 422(errors)
catch AuthorizationError:
  return 403(message)
catch Throwable:
  log(exception)
  return 500(generic_message)
```
