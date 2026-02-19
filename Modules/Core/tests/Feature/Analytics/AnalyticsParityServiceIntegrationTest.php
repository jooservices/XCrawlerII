<?php

namespace Modules\Core\Tests\Feature\Analytics;

use Illuminate\Support\Facades\Redis;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityDaily;
use Modules\Core\Models\Mongo\Analytics\AnalyticsEntityTotals;
use Modules\Core\Services\AnalyticsFlushService;
use Modules\Core\Services\AnalyticsIngestService;
use Modules\Core\Services\AnalyticsParityService;
use Modules\Core\Tests\TestCase;
use Modules\JAV\Models\Jav;

class AnalyticsParityServiceIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['analytics.redis_prefix' => 'anl:counters:test']);
        $this->requireAnalyticsInfra();
        $this->cleanupRedis();
        AnalyticsEntityTotals::query()->delete();
        AnalyticsEntityDaily::query()->delete();
    }

    public function test_pipeline_flood_counts_are_flushed_and_parity_is_zero(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $ingest = app(AnalyticsIngestService::class);

        for ($i = 0; $i < 50; $i++) {
            $ingest->ingest([
                'event_id' => sprintf('it-view-%s-%d', $jav->uuid, $i),
                'domain' => 'jav',
                'entity_type' => 'movie',
                'entity_id' => $jav->uuid,
                'action' => 'view',
                'value' => 1,
                'occurred_at' => now()->toIso8601String(),
            ]);
        }

        for ($i = 0; $i < 12; $i++) {
            $ingest->ingest([
                'event_id' => sprintf('it-download-%s-%d', $jav->uuid, $i),
                'domain' => 'jav',
                'entity_type' => 'movie',
                'entity_id' => $jav->uuid,
                'action' => 'download',
                'value' => 1,
                'occurred_at' => now()->toIso8601String(),
            ]);
        }

        app(AnalyticsFlushService::class)->flush();

        $mongo = AnalyticsEntityTotals::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
            ->where('entity_id', $jav->uuid)
            ->first();

        $this->assertNotNull($mongo);
        $this->assertSame(50, (int) ($mongo->view ?? 0));
        $this->assertSame(12, (int) ($mongo->download ?? 0));

        $this->assertSame(50, (int) $jav->fresh()->views);
        $this->assertSame(12, (int) $jav->fresh()->downloads);

        $result = app(AnalyticsParityService::class)->check(10);

        $this->assertSame(1, $result['checked']);
        $this->assertSame(0, $result['mismatches']);
    }

    public function test_duplicate_event_id_is_counted_once(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $payload = [
            'event_id' => sprintf('it-dup-%s', $jav->uuid),
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => $jav->uuid,
            'action' => 'view',
            'value' => 1,
            'occurred_at' => now()->toIso8601String(),
        ];

        $ingest = app(AnalyticsIngestService::class);
        $ingest->ingest($payload);
        $ingest->ingest($payload);

        app(AnalyticsFlushService::class)->flush();

        $this->assertSame(1, (int) $jav->fresh()->views);

        $result = app(AnalyticsParityService::class)->check(10);
        $this->assertSame(0, $result['mismatches']);
    }

    public function test_parity_detects_mismatch_when_mysql_is_modified_out_of_band(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        app(AnalyticsIngestService::class)->ingest([
            'event_id' => sprintf('it-mismatch-%s', $jav->uuid),
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => $jav->uuid,
            'action' => 'view',
            'value' => 3,
            'occurred_at' => now()->toIso8601String(),
        ]);

        app(AnalyticsFlushService::class)->flush();

        $this->assertSame(3, (int) $jav->fresh()->views);

        Jav::query()->whereKey($jav->id)->update(['views' => 999]);

        $result = app(AnalyticsParityService::class)->check(10);

        $this->assertSame(1, $result['mismatches']);
        $this->assertSame((string) $jav->code, $result['rows'][0]['code']);
    }

    public function test_daily_bucket_uses_event_date_part_at_timezone_boundary(): void
    {
        $jav = Jav::factory()->create([
            'views' => 0,
            'downloads' => 0,
            'source' => 'onejav',
        ]);

        $ingest = app(AnalyticsIngestService::class);
        $ingest->ingest([
            'event_id' => sprintf('it-tz-%s-1', $jav->uuid),
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => $jav->uuid,
            'action' => 'view',
            'value' => 1,
            'occurred_at' => '2026-02-19T23:59:59-05:00',
        ]);
        $ingest->ingest([
            'event_id' => sprintf('it-tz-%s-2', $jav->uuid),
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => $jav->uuid,
            'action' => 'view',
            'value' => 1,
            'occurred_at' => '2026-02-20T00:00:01+07:00',
        ]);

        app(AnalyticsFlushService::class)->flush();

        $dayOne = AnalyticsEntityDaily::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
            ->where('entity_id', $jav->uuid)
            ->where('date', '2026-02-19')
            ->first();
        $this->assertNotNull($dayOne);
        $this->assertSame(1, (int) $dayOne->view);

        $dayTwo = AnalyticsEntityDaily::query()
            ->where('domain', 'jav')
            ->where('entity_type', 'movie')
            ->where('entity_id', $jav->uuid)
            ->where('date', '2026-02-20')
            ->first();
        $this->assertNotNull($dayTwo);
        $this->assertSame(1, (int) $dayTwo->view);
    }

    public function test_parity_ignores_orphan_mongo_totals_without_mysql_row(): void
    {
        AnalyticsEntityTotals::query()->create([
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'orphan-movie-uuid',
            'view' => 321,
            'download' => 12,
        ]);

        $result = app(AnalyticsParityService::class)->check(10);

        $this->assertSame(0, $result['checked']);
        $this->assertSame(0, $result['mismatches']);
        $this->assertSame([], $result['rows']);
    }

    private function requireAnalyticsInfra(): void
    {
        try {
            Redis::set('anl:infra:test', 1, 'EX', 5);
            Redis::del('anl:infra:test');
        } catch (\Throwable $exception) {
            $this->markTestSkipped('Redis unavailable for integration test: '.$exception->getMessage());
        }

        try {
            AnalyticsEntityTotals::query()->limit(1)->get();
        } catch (\Throwable $exception) {
            $this->markTestSkipped('MongoDB unavailable for integration test: '.$exception->getMessage());
        }
    }

    private function cleanupRedis(): void
    {
        $prefix = (string) config('analytics.redis_prefix', 'anl:counters:test');
        $counterKeys = Redis::keys("{$prefix}:*");
        if ($counterKeys !== [] && $counterKeys !== null) {
            Redis::del($counterKeys);
        }

        $dedupeKeys = Redis::keys('anl:evt:it-*');
        if ($dedupeKeys !== [] && $dedupeKeys !== null) {
            Redis::del($dedupeKeys);
        }
    }
}
