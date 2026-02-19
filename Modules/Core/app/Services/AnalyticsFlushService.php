<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityDaily;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\JAV\Models\Jav;

class AnalyticsFlushService
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_ACTIONS = ['view', 'download'];

    /**
     * @return array{keys_processed: int, errors: int}
     */
    public function flush(): array
    {
        $prefix = (string) config('analytics.redis_prefix', 'anl:counters');
        $keys = Redis::keys("{$prefix}:*");
        $processed = 0;
        $errors = 0;

        foreach ($keys as $key) {
            try {
                $this->flushKey((string) $key, $prefix);
                $processed++;
            } catch (\Throwable $exception) {
                $errors++;
                Log::error('Analytics flush error', [
                    'key' => (string) $key,
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        return ['keys_processed' => $processed, 'errors' => $errors];
    }

    private function flushKey(string $key, string $prefix): void
    {
        $prefixMarker = "{$prefix}:";
        $prefixPos = strpos($key, $prefixMarker);
        if ($prefixPos === false) {
            Log::warning('Analytics: malformed counter key', ['key' => $key]);

            return;
        }

        $suffix = substr($key, $prefixPos + strlen($prefixMarker));
        $parts = explode(':', (string) $suffix, 3);
        if (count($parts) !== 3) {
            Log::warning('Analytics: malformed counter key', ['key' => $key]);

            return;
        }

        [$domain, $entityType, $entityId] = $parts;
        $canonicalKey = "{$prefix}:{$domain}:{$entityType}:{$entityId}";
        $tempKey = sprintf('anl:flushing:%s:%s:%s:%s', $domain, $entityType, $entityId, Str::random(8));

        try {
            $renamed = Redis::rename($canonicalKey, $tempKey);
        } catch (\Throwable) {
            return;
        }
        if (! $renamed) {
            return;
        }

        $counters = Redis::hgetall($tempKey);
        Redis::del($tempKey);

        if ($counters === [] || $counters === null) {
            return;
        }

        $totalIncrements = [];

        foreach ($counters as $field => $value) {
            $fieldName = (string) $field;
            $intValue = (int) $value;
            if ($intValue === 0) {
                continue;
            }

            if (str_contains($fieldName, ':')) {
                [$action, $date] = explode(':', $fieldName, 2);
                if (! in_array($action, self::SUPPORTED_ACTIONS, true)) {
                    continue;
                }

                $this->incrementDailyCounter($domain, $entityType, $entityId, $date, $action, $intValue);

                continue;
            }

            if (! in_array($fieldName, self::SUPPORTED_ACTIONS, true)) {
                continue;
            }

            $totalIncrements[$fieldName] = ($totalIncrements[$fieldName] ?? 0) + $intValue;
        }

        if ($totalIncrements !== []) {
            $this->incrementTotalCounters($domain, $entityType, $entityId, $totalIncrements);
        }

        if ($domain === 'jav' && $entityType === 'movie') {
            $this->syncToMySql($entityId);
        }
    }

    private function incrementDailyCounter(
        string $domain,
        string $entityType,
        string $entityId,
        string $date,
        string $action,
        int $value
    ): void {
        $daily = AnalyticsEntityDaily::query()->firstOrCreate(
            [
                'domain' => $domain,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'date' => $date,
            ],
            [
                'view' => 0,
                'download' => 0,
            ]
        );

        $daily->{$action} = (int) ($daily->{$action} ?? 0) + $value;
        $daily->save();
    }

    /**
     * @param  array<string, int>  $increments
     */
    private function incrementTotalCounters(string $domain, string $entityType, string $entityId, array $increments): void
    {
        $totals = AnalyticsEntityTotals::query()->firstOrCreate(
            [
                'domain' => $domain,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ],
            [
                'view' => 0,
                'download' => 0,
            ]
        );

        foreach ($increments as $action => $value) {
            $totals->{$action} = (int) ($totals->{$action} ?? 0) + (int) $value;
        }

        $totals->save();
    }

    private function syncToMySql(string $entityId): void
    {
        $totals = AnalyticsEntityTotals::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
            ->where('entity_id', $entityId)
            ->first();

        if ($totals === null) {
            return;
        }

        $views = (int) data_get($totals, 'view', 0);
        $downloads = (int) data_get($totals, 'download', 0);

        Jav::query()
            ->where('uuid', $entityId)
            ->update([
                'views' => $views,
                'downloads' => $downloads,
            ]);
    }
}
