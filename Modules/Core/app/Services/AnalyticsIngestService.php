<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Redis;

class AnalyticsIngestService
{
    /**
     * @param  array{domain: string, entity_type: string, entity_id: string, action: string, value?: int, occurred_at: string}  $event
     */
    public function ingest(array $event, ?int $userId = null): void
    {
        $prefix = (string) config('analytics.redis_prefix', 'anl:counters');
        $dedupeKey = sprintf('anl:evt:%s', $event['event_id']);

        // Deduplicate: exact same event_id ignored for 48h
        $isNew = Redis::set($dedupeKey, 1, 'NX', 'EX', 172800);
        if (! $isNew) {
            return;
        }

        $key = implode(':', [
            $prefix,
            $event['domain'],
            $event['entity_type'],
            $event['entity_id'],
        ]);

        $value = (int) ($event['value'] ?? 1);
        $action = (string) $event['action'];
        $date = mb_substr((string) $event['occurred_at'], 0, 10);

        Redis::hincrby($key, $action, $value);
        Redis::hincrby($key, sprintf('%s:%s', $action, $date), $value);
    }
}
