<?php

namespace Modules\Core\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;

/**
 * Writes validated analytics events into Redis hot counters with dedupe keys.
 */
class AnalyticsIngestService
{
    private const DEDUPE_PREFIX = 'anl:evt:';

    private const DEDUPE_TTL_SECONDS = 172800;

    /**
     * @param  array{event_id: string, domain: string, entity_type: string, entity_id: string, action: string, value?: int, occurred_at: string}  $event
     */
    public function ingest(array $event, ?int $userId = null): void
    {
        /** @var string $prefix */
        $prefix = config('analytics.redis_prefix', 'anl:counters');
        $domain = AnalyticsDomain::from((string) $event['domain']);
        $entityType = AnalyticsEntityType::from((string) $event['entity_type']);
        $action = AnalyticsAction::from((string) $event['action']);
        $dedupeKey = self::DEDUPE_PREFIX.(string) $event['event_id'];

        // Deduplicate: exact same event_id ignored for 48h
        /** @phpstan-ignore-next-line */
        $isNew = Redis::set($dedupeKey, '1', 'EX', self::DEDUPE_TTL_SECONDS, 'NX');
        if ($isNew !== true && $isNew !== 'OK') {
            return;
        }

        $key = implode(':', [
            $prefix,
            $domain->value,
            $entityType->value,
            $event['entity_id'],
        ]);

        $value = (int) ($event['value'] ?? 1);
        $actionName = $action->value;
        $date = Carbon::parse((string) $event['occurred_at'])->toDateString();

        /** @phpstan-ignore-next-line */
        Redis::hincrby($key, $actionName, $value);
        /** @phpstan-ignore-next-line */
        Redis::hincrby($key, sprintf('%s:%s', $actionName, $date), $value);
    }
}
