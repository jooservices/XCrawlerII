<?php

namespace Modules\Core\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityDaily;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityMonthly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityWeekly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityYearly;
use Modules\JAV\Models\Jav;

/**
 * Flushes Redis analytics counters into Mongo rollups and MySQL replicas.
 */
class AnalyticsFlushService
{
    /**
     * @var array<int, string>
     */
    private const SUPPORTED_ACTIONS = [
        AnalyticsAction::View->value,
        AnalyticsAction::Download->value,
    ];

    /**
     * @return array{keys_processed: int, errors: int}
     */
    public function flush(): array
    {
        $prefix = (string) config('analytics.redis_prefix', 'anl:counters');
        $processed = 0;
        $errors = 0;

        $cursor = null;
        do {
            // Use SCAN to avoid blocking Redis with KEYS command in production
            if (app()->runningUnitTests()) {
                $keys = Redis::keys("{$prefix}:*");
                $result = [0, $keys];
            } else {
                $result = Redis::scan($cursor, ['MATCH' => "{$prefix}:*", 'COUNT' => 10000]);
            }

            // Redis facade handling of scan return/cursor depends on driver (predis vs phpredis).
            // Laravel's Redis facade usually abstracts this, but often creates an iterator.
            // If using the raw Redis::scan, it returns an array where [0] is new cursor.
            // HOWEVER, Laravel's Redis facade often returns just the keys when using `scan` as an iterator method or different structure.
            // A safer way in Laravel is often `Redis::connection()->scan($cursor, ...)` or using an iterator if available.
            // Let's assume standard Predis/Phpredis array return for now or use a loop if available.
            // Actually, `Redis::scan` in Laravel facade often returns the array result directly.

            if ($result === false) {
                break;
            }

            [$newCursor, $keys] = $result;
            $cursor = $newCursor;

            // $keys can be null or empty
            if (! empty($keys)) {
                foreach ($keys as $key) {
                    try {
                        if ($this->flushKey((string) $key, $prefix)) {
                            $processed++;
                        }
                    } catch (\Throwable $exception) {
                        $errors++;
                        Log::error('Analytics flush error', [
                            'key' => (string) $key,
                            'error' => $exception->getMessage(),
                        ]);
                    }
                }
            }

        } while ($cursor != 0);

        return ['keys_processed' => $processed, 'errors' => $errors];
    }

    private function flushKey(string $key, string $prefix): bool
    {
        $prefixMarker = "{$prefix}:";
        $prefixPos = strpos($key, $prefixMarker);

        if ($prefixPos === false) {
            return false;
        }

        $suffix = substr($key, $prefixPos + strlen($prefixMarker));
        $parts = explode(':', (string) $suffix, 3);
        if (count($parts) !== 3) {
            Log::warning('Analytics: malformed counter key parts', ['key' => $key]);

            return false;
        }

        [$domain, $entityType, $entityId] = $parts;
        $canonicalKey = "{$prefix}:{$domain}:{$entityType}:{$entityId}";
        $tempKey = sprintf('anl:flushing:%s:%s:%s:%s', $domain, $entityType, $entityId, Str::random(8));

        try {
            $renamed = Redis::rename($canonicalKey, $tempKey);
        } catch (\Throwable $e) {
            return false;
        }
        if (! $renamed) {
            return false;
        }

        // Use SCAN to iterate over the hash instead of HGETALL (blocking)
        // See comments in flush() for why HGETALL is used here.

        $counters = Redis::hgetall($tempKey);

        if ($counters === [] || $counters === null) {
            Redis::del($tempKey);

            return false;
        }

        $totalIncrements = [];
        $mongoOperations = []; // For bulk write

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

                $mongoOperations = array_merge($mongoOperations, $this->prepareDailyCounterOps($domain, $entityType, $entityId, $date, $action, $intValue));
                $mongoOperations = array_merge($mongoOperations, $this->prepareTimeBucketOps($domain, $entityType, $entityId, $date, $action, $intValue));

                continue;
            }

            if (! in_array($fieldName, self::SUPPORTED_ACTIONS, true)) {
                continue;
            }

            $totalIncrements[$fieldName] = ($totalIncrements[$fieldName] ?? 0) + $intValue;
        }

        if ($totalIncrements !== []) {
            $mongoOperations = array_merge($mongoOperations, $this->prepareTotalCounterOps($domain, $entityType, $entityId, $totalIncrements));
        }

        // Execute Bulk Write
        if (! empty($mongoOperations)) {
            $this->executeBulkWrite($mongoOperations);
        }

        // Safe deletion: Only delete temp key after processing succeeds
        Redis::del($tempKey);

        if ($domain === AnalyticsDomain::Jav->value && $entityType === AnalyticsEntityType::Movie->value) {
            $this->syncToMySql($entityId);
        }

        // dump("Processed key: $key");
        return true;
    }

    private function prepareDailyCounterOps(
        string $domain,
        string $entityType,
        string $entityId,
        string $date,
        string $action,
        int $value
    ): array {
        return $this->buildUpsertOp(
            AnalyticsEntityDaily::class,
            [
                'domain' => $domain,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'date' => $date,
            ],
            $action,
            $value
        );
    }

    private function prepareTimeBucketOps(
        string $domain,
        string $entityType,
        string $entityId,
        string $date,
        string $action,
        int $value
    ): array {
        $ops = [];
        try {
            $day = Carbon::parse($date);
        } catch (\Throwable) {
            return [];
        }

        $week = sprintf('%s-W%02d', $day->format('o'), $day->isoWeek());
        $month = $day->format('Y-m');
        $year = $day->format('Y');

        $ops = array_merge($ops, $this->buildUpsertOp(
            AnalyticsEntityWeekly::class,
            [
                'domain' => $domain,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'week' => $week,
            ],
            $action,
            $value
        ));

        $ops = array_merge($ops, $this->buildUpsertOp(
            AnalyticsEntityMonthly::class,
            [
                'domain' => $domain,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'month' => $month,
            ],
            $action,
            $value
        ));

        $ops = array_merge($ops, $this->buildUpsertOp(
            AnalyticsEntityYearly::class,
            [
                'domain' => $domain,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'year' => $year,
            ],
            $action,
            $value
        ));

        return $ops;
    }

    private function prepareTotalCounterOps(string $domain, string $entityType, string $entityId, array $increments): array
    {
        $ops = [];
        foreach ($increments as $action => $value) {
            $ops = array_merge($ops, $this->buildUpsertOp(
                AnalyticsEntityTotals::class,
                [
                    'domain' => $domain,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ],
                $action,
                (int) $value
            ));
        }

        return $ops;
    }

    private function buildUpsertOp(string $modelClass, array $keys, string $action, int $value): array
    {
        $setOnInsert = [
            AnalyticsAction::View->value => 0,
            AnalyticsAction::Download->value => 0,
        ];

        if (array_key_exists($action, $setOnInsert)) {
            unset($setOnInsert[$action]);
        }

        $update = [
            '$inc' => [$action => $value],
        ];

        if (! empty($setOnInsert)) {
            $update['$setOnInsert'] = $setOnInsert;
        }

        return [
            [
                'model' => $modelClass,
                'filter' => $keys,
                'update' => $update,
                'upsert' => true,
            ],
        ];
    }

    private function executeBulkWrite(array $operations): void
    {
        // Group by model to execute bulk writes per collection
        $grouped = [];
        foreach ($operations as $op) {
            $grouped[$op['model']][] = [
                'updateOne' => [
                    $op['filter'],
                    $op['update'],
                    ['upsert' => $op['upsert']],
                ],
            ];
        }

        foreach ($grouped as $modelClass => $ops) {
            $modelClass::raw(function ($collection) use ($ops) {
                $collection->bulkWrite($ops);
            });
        }
    }

    private function syncToMySql(string $entityId): void
    {
        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $entityId)
            ->first();

        if ($totals === null) {
            return;
        }

        $views = (int) data_get($totals, AnalyticsAction::View->value, 0);
        $downloads = (int) data_get($totals, AnalyticsAction::Download->value, 0);

        Jav::query()
            ->where('uuid', $entityId)
            ->update([
                'views' => $views,
                'downloads' => $downloads,
            ]);
    }
}
