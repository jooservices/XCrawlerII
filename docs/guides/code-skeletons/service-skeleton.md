# Service Skeleton

```php
<?php

declare(strict_types=1);

namespace Modules\JAV\Services;

final class ExampleDomainService
{
    /**
     * Build a filtered payload used by controllers.
     *
     * Why: keep controller thin and isolate business rules in one testable unit.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function buildPayload(array $filters): array
    {
        // 1) Normalize filters and enforce defaults.
        // 2) Query repositories/search providers.
        // 3) Merge and transform to API/UI contract.
        // 4) Return deterministic structure.

        return [
            'items' => [],
            'meta' => [
                'total' => 0,
            ],
        ];
    }
}
```

## Pseudo-code (Critical Function Pattern)

```text
function buildPayload(filters):
  normalized = normalize(filters)
  guard(normalized)
  base = repository.search(normalized)
  enriched = decorateWithUserContext(base)
  return responseShape(enriched)
```
