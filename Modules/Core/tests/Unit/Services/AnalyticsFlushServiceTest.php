<?php

namespace Modules\Core\Tests\Unit\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityDaily;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityMonthly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityWeekly;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityYearly;
use Modules\Core\Services\AnalyticsFlushService;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Jav;

class AnalyticsFlushServiceTest extends TestCase
{
    private string $prefix;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requireAnalyticsInfra();
        $this->prefix = 'anl:counters:test:flush:'.$this->faker->uuid();
        config(['analytics.redis_prefix' => $this->prefix]);

        $this->cleanupRedisByPattern("{$this->prefix}:*");
        AnalyticsEntityTotals::query()->delete();
        AnalyticsEntityDaily::query()->delete();
        AnalyticsEntityWeekly::query()->delete();
        AnalyticsEntityMonthly::query()->delete();
        AnalyticsEntityYearly::query()->delete();
    }

    public function test_flush_is_noop_when_no_redis_keys(): void
    {
        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 0, 'errors' => 0], $result);
    }

    public function test_flush_single_key_writes_totals_daily_and_syncs_mysql_for_movie(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $counterKey = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$jav->uuid}";
        $date = now()->toDateString();

        Redis::hset($counterKey, AnalyticsAction::View->value, 5);
        Redis::hset($counterKey, AnalyticsAction::Download->value, 2);
        Redis::hset($counterKey, AnalyticsAction::View->value.":{$date}", 5);
        Redis::hset($counterKey, AnalyticsAction::Download->value.":{$date}", 2);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(5, (int) $totals->view);
        $this->assertSame(2, (int) $totals->download);

        $daily = AnalyticsEntityDaily::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->where('date', $date)
            ->first();
        $this->assertNotNull($daily);
        $this->assertSame(5, (int) $daily->view);
        $this->assertSame(2, (int) $daily->download);

        $day = Carbon::parse($date);
        $week = sprintf('%s-W%02d', $day->format('o'), $day->isoWeek());
        $month = $day->format('Y-m');
        $year = $day->format('Y');

        $weekly = AnalyticsEntityWeekly::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->where('week', $week)
            ->first();
        $this->assertNotNull($weekly);
        $this->assertSame(5, (int) $weekly->view);
        $this->assertSame(2, (int) $weekly->download);

        $monthly = AnalyticsEntityMonthly::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->where('month', $month)
            ->first();
        $this->assertNotNull($monthly);
        $this->assertSame(5, (int) $monthly->view);
        $this->assertSame(2, (int) $monthly->download);

        $yearly = AnalyticsEntityYearly::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->where('year', $year)
            ->first();
        $this->assertNotNull($yearly);
        $this->assertSame(5, (int) $yearly->view);
        $this->assertSame(2, (int) $yearly->download);

        $jav->refresh();
        $this->assertSame(5, (int) $jav->views);
        $this->assertSame(2, (int) $jav->downloads);
    }

    public function test_malformed_key_is_skipped_without_creating_counters(): void
    {
        Redis::set("{$this->prefix}:badkey", '1');

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);
        $this->assertSame(0, AnalyticsEntityTotals::query()->count());
        $this->assertSame(0, AnalyticsEntityDaily::query()->count());
        $this->assertSame(0, AnalyticsEntityWeekly::query()->count());
        $this->assertSame(0, AnalyticsEntityMonthly::query()->count());
        $this->assertSame(0, AnalyticsEntityYearly::query()->count());
    }

    public function test_non_movie_entity_updates_mongo_but_does_not_sync_mysql_movie_row(): void
    {
        $jav = Jav::factory()->create([
            'views' => 77,
            'downloads' => 11,
            'source' => 'onejav',
        ]);

        $actorId = $this->faker->uuid();
        $counterKey = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Actor->value.":{$actorId}";
        $date = now()->toDateString();

        Redis::hset($counterKey, AnalyticsAction::View->value, 4);
        Redis::hset($counterKey, AnalyticsAction::View->value.":{$date}", 4);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Actor->value)
            ->where('entity_id', $actorId)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(4, (int) $totals->view);

        $day = Carbon::parse($date);
        $week = sprintf('%s-W%02d', $day->format('o'), $day->isoWeek());
        $month = $day->format('Y-m');
        $year = $day->format('Y');

        $this->assertNotNull(AnalyticsEntityWeekly::query()
            ->where('entity_type', AnalyticsEntityType::Actor->value)
            ->where('entity_id', $actorId)
            ->where('week', $week)
            ->first());
        $this->assertNotNull(AnalyticsEntityMonthly::query()
            ->where('entity_type', AnalyticsEntityType::Actor->value)
            ->where('entity_id', $actorId)
            ->where('month', $month)
            ->first());
        $this->assertNotNull(AnalyticsEntityYearly::query()
            ->where('entity_type', AnalyticsEntityType::Actor->value)
            ->where('entity_id', $actorId)
            ->where('year', $year)
            ->first());

        $jav->refresh();
        $this->assertSame(77, (int) $jav->views);
        $this->assertSame(11, (int) $jav->downloads);
    }

    public function test_flush_twice_on_same_key_does_not_double_count(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $counterKey = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$jav->uuid}";
        $date = now()->toDateString();

        Redis::hset($counterKey, AnalyticsAction::View->value, 7);
        Redis::hset($counterKey, AnalyticsAction::View->value.":{$date}", 7);

        $service = new AnalyticsFlushService;
        $first = $service->flush();
        $second = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $first);
        $this->assertSame(['keys_processed' => 0, 'errors' => 0], $second);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(7, (int) $totals->view);

        $jav->refresh();
        $this->assertSame(7, (int) $jav->views);
    }

    public function test_large_counter_values_are_flushed_correctly(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $counterKey = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$jav->uuid}";
        $date = now()->toDateString();

        for ($i = 0; $i < 200; $i++) {
            Redis::hincrby($counterKey, AnalyticsAction::View->value, 100);
            Redis::hincrby($counterKey, AnalyticsAction::View->value.":{$date}", 100);
        }

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(20000, (int) $totals->view);

        $daily = AnalyticsEntityDaily::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->where('date', $date)
            ->first();
        $this->assertNotNull($daily);
        $this->assertSame(20000, (int) $daily->view);

        $jav->refresh();
        $this->assertSame(20000, (int) $jav->views);
    }

    public function test_unsupported_action_fields_are_ignored_during_flush(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $counterKey = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$jav->uuid}";
        $date = now()->toDateString();

        Redis::hset($counterKey, AnalyticsAction::View->value, 4);
        Redis::hset($counterKey, AnalyticsAction::View->value.":{$date}", 4);
        Redis::hset($counterKey, 'favorite', 999);
        Redis::hset($counterKey, "favorite:{$date}", 999);
        Redis::hset($counterKey, AnalyticsAction::Download->value, 1);
        Redis::hset($counterKey, AnalyticsAction::Download->value.":{$date}", 1);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(4, (int) $totals->view);
        $this->assertSame(1, (int) $totals->download);
        $this->assertArrayNotHasKey('favorite', $totals->toArray());
    }

    public function test_malformed_date_in_daily_field_does_not_crash_flush(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $counterKey = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$jav->uuid}";
        $validDate = now()->toDateString();

        Redis::hset($counterKey, AnalyticsAction::View->value, 2);
        Redis::hset($counterKey, AnalyticsAction::View->value.":{$validDate}", 2);
        Redis::hset($counterKey, AnalyticsAction::View->value.':not-a-valid-date', 3);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);
        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(2, (int) $totals->view);
    }

    public function test_flush_movie_entity_with_no_jav_row_updates_mongo_only_not_mysql(): void
    {
        $nonExistentUuid = $this->faker->uuid();
        $counterKey = "{$this->prefix}:".AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$nonExistentUuid}";
        $date = now()->toDateString();

        Redis::hset($counterKey, AnalyticsAction::View->value, 10);
        Redis::hset($counterKey, AnalyticsAction::View->value.":{$date}", 10);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', AnalyticsDomain::Jav->value)
            ->where('entity_type', AnalyticsEntityType::Movie->value)
            ->where('entity_id', $nonExistentUuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(10, (int) $totals->view);

        $javRow = Jav::query()->where('uuid', $nonExistentUuid)->first();
        $this->assertNull($javRow);
    }

    private function requireAnalyticsInfra(): void
    {
        try {
            Redis::set('anl:infra:test:flush', 1, 'EX', 5);
            Redis::del('anl:infra:test:flush');
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Redis unavailable for analytics flush test: '.$exception->getMessage());
        }

        try {
            AnalyticsEntityTotals::query()->limit(1)->get();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('MongoDB unavailable for analytics flush test: '.$exception->getMessage());
        }
    }

    private function cleanupRedisByPattern(string $pattern): void
    {
        $keys = Redis::keys($pattern);
        if ($keys !== [] && $keys !== null) {
            Redis::del($keys);
        }
    }
}
