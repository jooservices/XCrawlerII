# Controller Skeleton

```php
<?php

declare(strict_types=1);

namespace Modules\JAV\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\JAV\Http\Requests\AnalyticsApiRequest;
use Modules\JAV\Services\ActorAnalyticsService;

final class AnalyticsControllerSkeleton extends Controller
{
    public function __construct(private readonly ActorAnalyticsService $service)
    {
    }

    /**
     * Return distribution analytics for a validated segment query.
     *
     * Why: Keep HTTP layer predictable and delegate domain logic to service layer.
     */
    public function distribution(AnalyticsApiRequest $request): JsonResponse
    {
        $payload = $request->validated();

        // Complex branching (dimension/genre constraints) belongs in service.
        $result = $this->service->distribution(
            (string) ($payload['dimension'] ?? 'age_bucket'),
            (string) ($payload['genre'] ?? ''),
            (int) ($payload['size'] ?? 10),
        );

        return response()->json($result);
    }
}
```

## Pseudo-code Pattern

```text
validate request
normalize small controller-level defaults
call one service method
map exceptions to stable HTTP errors
return strict JSON contract
```
