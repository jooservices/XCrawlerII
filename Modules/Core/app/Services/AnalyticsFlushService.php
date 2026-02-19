<?php

namespace Modules\Core\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class AnalyticsFlushService
{
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
        $keyWithoutPrefix = str_replace("{$prefix}:", '', $key);
        $parts = explode(':', $keyWithoutPrefix, 3);

        if (count($parts) !== 3) {
            Log::warning('Analytics: malformed counter key', ['key' => $key]);

            return;
        }

        [$domain, $entityType, $entityId] = $parts;
        $tempKey = sprintf('anl:flushing:%s:%s:%s:%s', $domain, $entityType, $entityId, Str::random(8));

        try {
            Redis::rename($key, $tempKey);
        } catch (\Throwable) {
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
                DB::connection('mongodb')
                    ->collection('analytics_entity_daily')
                    ->updateOrInsert(
                        [
                            'domain' => $domain,
                            'entity_type' => $entityType,
                            'entity_id' => $entityId,
                            'date' => $date,
                        ],
                        ['$inc' => [$action => $intValue]]
                    );

                continue;
            }

            $totalIncrements[$fieldName] = ($totalIncrements[$fieldName] ?? 0) + $intValue;
        }

        if ($totalIncrements !== []) {
            DB::connection('mongodb')
                ->collection('analytics_entity_totals')
                ->updateOrInsert(
                    [
                        'domain' => $domain,
                        'entity_type' => $entityType,
                        'entity_id' => $entityId,
                    ],
                    ['$inc' => $totalIncrements]
                );
        }

        if ($domain === 'jav' && $entityType === 'movie') {
            $this->syncToMySql($domain, $entityType, $entityId);
        }
    }

    private function syncToMySql(string $domain, string $entityType, string $entityId): void
    {
        $totals = DB::connection('mongodb')
            ->collection('analytics_entity_totals')
            ->where('domain', $domain)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->first();

        if (! is_array($totals) && ! is_object($totals)) {
            return;
        }

        $views = (int) data_get($totals, 'view', 0);
        $downloads = (int) data_get($totals, 'download', 0);

        DB::table('jav')
            ->where('uuid', $entityId)
            ->update([
                'views' => $views,
                'downloads' => $downloads,
            ]);
    }
}
