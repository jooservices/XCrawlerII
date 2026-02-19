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
                $this->incrementTimeBuckets($domain, $entityType, $entityId, $date, $action, $intValue);

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

        if ($domain === AnalyticsDomain::Jav->value && $entityType === AnalyticsEntityType::Movie->value) {
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
        $this->incrementBucket(
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

    private function incrementTimeBuckets(
        string $domain,
        string $entityType,
        string $entityId,
        string $date,
        string $action,
        int $value
    ): void {
        try {
            $day = Carbon::parse($date);
        } catch (\Throwable) {
            return;
        }

        $week = sprintf('%s-W%02d', $day->format('o'), $day->isoWeek());
        $month = $day->format('Y-m');
        $year = $day->format('Y');

        $this->incrementBucket(
            AnalyticsEntityWeekly::class,
            [
                'domain' => $domain,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'week' => $week,
            ],
            $action,
            $value
        );

        $this->incrementBucket(
            AnalyticsEntityMonthly::class,
            [
                'domain' => $domain,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'month' => $month,
            ],
            $action,
            $value
        );

        $this->incrementBucket(
            AnalyticsEntityYearly::class,
            [
                'domain' => $domain,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'year' => $year,
            ],
            $action,
            $value
        );
    }

    /**
     * @param  array<string, int>  $increments
     */
    private function incrementTotalCounters(string $domain, string $entityType, string $entityId, array $increments): void
    {
        foreach ($increments as $action => $value) {
            $this->incrementBucket(
                AnalyticsEntityTotals::class,
                [
                    'domain' => $domain,
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                ],
                $action,
                (int) $value
            );
        }
    }

    /**
     * @param  class-string<\MongoDB\Laravel\Eloquent\Model>  $modelClass
     * @param  array<string, string>  $keys
     */
    private function incrementBucket(string $modelClass, array $keys, string $action, int $value): void
    {
        $model = $modelClass::query()->firstOrCreate($keys, [
            AnalyticsAction::View->value => 0,
            AnalyticsAction::Download->value => 0,
        ]);

        $model->{$action} = (int) ($model->{$action} ?? 0) + $value;
        $model->save();
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
