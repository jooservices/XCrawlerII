<?php

namespace Modules\Core\Tests\Unit\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Modules\Core\Services\AnalyticsFlushService;
use Modules\Core\Tests\TestCase;

class AnalyticsFlushServiceTest extends TestCase
{
    public function test_flush_is_noop_when_no_redis_keys(): void
    {
        Redis::shouldReceive('keys')->once()->with('anl:counters:*')->andReturn([]);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 0, 'errors' => 0], $result);
    }

    public function test_flush_single_key_writes_totals_daily_and_syncs_mysql_for_movie(): void
    {
        Redis::shouldReceive('keys')->once()->with('anl:counters:*')->andReturn([
            'anl:counters:jav:movie:uuid-123',
        ]);
        Redis::shouldReceive('rename')->once()->withArgs(function (string $from, string $to): bool {
            return $from === 'anl:counters:jav:movie:uuid-123'
                && str_starts_with($to, 'anl:flushing:jav:movie:uuid-123:');
        });
        Redis::shouldReceive('hgetall')->once()->andReturn([
            'view' => 5,
            'download' => 2,
            'view:2026-02-19' => 3,
        ]);
        Redis::shouldReceive('del')->once();

        $mongoConn = \Mockery::mock();
        $dailyCollection = \Mockery::mock();
        $totalsCollection = \Mockery::mock();
        $javTable = \Mockery::mock();

        DB::shouldReceive('connection')->with('mongodb')->andReturn($mongoConn);
        $mongoConn->shouldReceive('collection')->with('analytics_entity_daily')->once()->andReturn($dailyCollection);
        $dailyCollection->shouldReceive('updateOrInsert')->once()->with(
            [
                'domain' => 'jav',
                'entity_type' => 'movie',
                'entity_id' => 'uuid-123',
                'date' => '2026-02-19',
            ],
            ['$inc' => ['view' => 3]]
        );

        $mongoConn->shouldReceive('collection')->times(2)->with('analytics_entity_totals')->andReturn($totalsCollection);
        $totalsCollection->shouldReceive('updateOrInsert')->once()->with(
            [
                'domain' => 'jav',
                'entity_type' => 'movie',
                'entity_id' => 'uuid-123',
            ],
            ['$inc' => ['view' => 5, 'download' => 2]]
        );
        $totalsCollection->shouldReceive('where')->times(3)->andReturnSelf();
        $totalsCollection->shouldReceive('first')->once()->andReturn([
            'view' => 8,
            'download' => 3,
        ]);

        DB::shouldReceive('table')->once()->with('jav')->andReturn($javTable);
        $javTable->shouldReceive('where')->once()->with('uuid', 'uuid-123')->andReturnSelf();
        $javTable->shouldReceive('update')->once()->with([
            'views' => 8,
            'downloads' => 3,
        ])->andReturn(1);

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);
    }

    public function test_malformed_key_is_skipped_with_warning(): void
    {
        Redis::shouldReceive('keys')->once()->andReturn(['anl:counters:badkey']);
        Log::shouldReceive('warning')->once()->withArgs(function (string $message, array $context): bool {
            return $message === 'Analytics: malformed counter key'
                && ($context['key'] ?? null) === 'anl:counters:badkey';
        });

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);
    }

    public function test_rename_failure_is_gracefully_skipped(): void
    {
        Redis::shouldReceive('keys')->once()->andReturn(['anl:counters:jav:movie:uuid-123']);
        Redis::shouldReceive('rename')->once()->andThrow(new \RuntimeException('rename failed'));

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);
    }

    public function test_non_movie_entity_does_not_sync_mysql(): void
    {
        Redis::shouldReceive('keys')->once()->andReturn(['anl:counters:jav:actor:actor-1']);
        Redis::shouldReceive('rename')->once();
        Redis::shouldReceive('hgetall')->once()->andReturn([
            'view' => 10,
            'view:2026-02-19' => 4,
        ]);
        Redis::shouldReceive('del')->once();

        $mongoConn = \Mockery::mock();
        $dailyCollection = \Mockery::mock();
        $totalsCollection = \Mockery::mock();

        DB::shouldReceive('connection')->with('mongodb')->andReturn($mongoConn);
        $mongoConn->shouldReceive('collection')->with('analytics_entity_daily')->once()->andReturn($dailyCollection);
        $dailyCollection->shouldReceive('updateOrInsert')->once();
        $mongoConn->shouldReceive('collection')->with('analytics_entity_totals')->once()->andReturn($totalsCollection);
        $totalsCollection->shouldReceive('updateOrInsert')->once();

        DB::shouldReceive('table')->never();

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 1, 'errors' => 0], $result);
    }

    public function test_flush_counts_errors_when_key_processing_throws(): void
    {
        Redis::shouldReceive('keys')->once()->andReturn(['anl:counters:jav:movie:uuid-123']);
        Redis::shouldReceive('rename')->once();
        Redis::shouldReceive('hgetall')->once()->andThrow(new \RuntimeException('hgetall failed'));

        Log::shouldReceive('error')->once()->withArgs(function (string $message, array $context): bool {
            return $message === 'Analytics flush error'
                && ($context['key'] ?? null) === 'anl:counters:jav:movie:uuid-123';
        });

        $service = new AnalyticsFlushService;
        $result = $service->flush();

        $this->assertSame(['keys_processed' => 0, 'errors' => 1], $result);
    }
}
