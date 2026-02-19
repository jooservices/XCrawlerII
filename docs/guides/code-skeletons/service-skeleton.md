# Service Skeleton

```php
<?php

declare(strict_types=1);

namespace Modules\Core\Services;

final class AnalyticsIngestServiceSkeleton
{
    /**
     * Ingest one validated analytics event into hot counters.
     *
     * Why: absorb high write volume without directly stressing persistent stores.
     *
     * @param array<string,mixed> $event
     */
    public function ingest(array $event, ?int $userId = null): void
    {
        // Build dedupe key from event_id.
        // Reject duplicate events in active dedupe window.
        // Compute Redis counter key from domain/entity_type/entity_id.
        // Increment total action counter and date-scoped action counter.
    }

    /**
     * Build deterministic Redis key for one entity counter bucket.
     */
    public function buildCounterKey(string $prefix, string $domain, string $entityType, string $entityId): string
    {
        return sprintf('%s:%s:%s:%s', $prefix, $domain, $entityType, $entityId);
    }
}
```

## Critical Function Pseudo-code

```text
if dedupe_key already exists -> return
set dedupe_key with ttl
counter_key = prefix + domain + entity_type + entity_id
increment hash[action] by value
increment hash[action:YYYY-MM-DD] by value
```
