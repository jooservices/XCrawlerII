<?php

namespace Modules\Core\Tests\Unit\Services;

use Illuminate\Support\Facades\Redis;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityDaily;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
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
        $this->prefix = 'anl:counters:test:flush:'.uniqid();
        config(['analytics.redis_prefix' => $this->prefix]);

        $this->cleanupRedisByPattern("{$this->prefix}:*");
        AnalyticsEntityTotals::query()->delete();
        AnalyticsEntityDaily::query()->delete();
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

        $counterKey = "{$this->prefix}:jav:movie:{$jav->uuid}";
        $date = now()->toDateString();

        Redis::hset($counterKey, 'view', 5);
        Redis::hset($counterKey, 'download', 2);
        Redis::hset($counterKey, "view:{$date}", 5);
        Redis::hset($counterKey, "download:{$date}", 2);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(5, (int) $totals->view);
        $this->assertSame(2, (int) $totals->download);

        $daily = AnalyticsEntityDaily::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
            ->where('entity_id', $jav->uuid)
            ->where('date', $date)
            ->first();
        $this->assertNotNull($daily);
        $this->assertSame(5, (int) $daily->view);
        $this->assertSame(2, (int) $daily->download);

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
    }

    public function test_non_movie_entity_updates_mongo_but_does_not_sync_mysql_movie_row(): void
    {
        $jav = Jav::factory()->create([
            'views' => 77,
            'downloads' => 11,
            'source' => 'onejav',
        ]);

        $counterKey = "{$this->prefix}:jav:actor:actor-1";
        $date = now()->toDateString();

        Redis::hset($counterKey, 'view', 4);
        Redis::hset($counterKey, "view:{$date}", 4);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'actor')
            ->where('entity_id', 'actor-1')
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(4, (int) $totals->view);

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

        $counterKey = "{$this->prefix}:jav:movie:{$jav->uuid}";
        $date = now()->toDateString();

        Redis::hset($counterKey, 'view', 7);
        Redis::hset($counterKey, "view:{$date}", 7);

        $service = new AnalyticsFlushService;
        $first = $service->flush();
        $second = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $first);
        $this->assertSame(['keys_processed' => 0, 'errors' => 0], $second);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
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

        $counterKey = "{$this->prefix}:jav:movie:{$jav->uuid}";
        $date = now()->toDateString();

        for ($i = 0; $i < 200; $i++) {
            Redis::hincrby($counterKey, 'view', 100);
            Redis::hincrby($counterKey, "view:{$date}", 100);
        }

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(20000, (int) $totals->view);

        $daily = AnalyticsEntityDaily::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
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

        $counterKey = "{$this->prefix}:jav:movie:{$jav->uuid}";
        $date = now()->toDateString();

        Redis::hset($counterKey, 'view', 4);
        Redis::hset($counterKey, "view:{$date}", 4);
        Redis::hset($counterKey, 'favorite', 999);
        Redis::hset($counterKey, "favorite:{$date}", 999);
        Redis::hset($counterKey, 'download', 1);
        Redis::hset($counterKey, "download:{$date}", 1);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);

        $totals = AnalyticsEntityTotals::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
            ->where('entity_id', $jav->uuid)
            ->first();
        $this->assertNotNull($totals);
        $this->assertSame(4, (int) $totals->view);
        $this->assertSame(1, (int) $totals->download);
        $this->assertArrayNotHasKey('favorite', $totals->toArray());
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
