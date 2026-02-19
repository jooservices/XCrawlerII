<?php

namespace Modules\Core\Tests\Unit\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Core\Services\AnalyticsParityService;
use Modules\Core\Tests\TestCase;

class AnalyticsParityServiceTest extends TestCase
{
    public function test_check_returns_zero_mismatch_when_values_match(): void
    {
        $mysqlQuery = \Mockery::mock();
        $mongoConn = \Mockery::mock();
        $mongoCollection = \Mockery::mock();

        DB::shouldReceive('table')->once()->with('jav')->andReturn($mysqlQuery);
        $mysqlQuery->shouldReceive('orderByDesc')->once()->with('views')->andReturnSelf();
        $mysqlQuery->shouldReceive('limit')->once()->with(100)->andReturnSelf();
        $mysqlQuery->shouldReceive('get')->once()->andReturn(new Collection([
            (object) ['uuid' => 'uuid-1', 'code' => 'ABC-001', 'views' => 10, 'downloads' => 3],
        ]));

        DB::shouldReceive('connection')->once()->with('mongodb')->andReturn($mongoConn);
        $mongoConn->shouldReceive('collection')->once()->with('analytics_entity_totals')->andReturn($mongoCollection);
        $mongoCollection->shouldReceive('where')->times(3)->andReturnSelf();
        $mongoCollection->shouldReceive('first')->once()->andReturn(['view' => 10, 'download' => 3]);

        $service = new AnalyticsParityService;
        $result = $service->check();

        $this->assertSame(1, $result['checked']);
        $this->assertSame(0, $result['mismatches']);
        $this->assertCount(0, $result['rows']);
    }

    public function test_check_collects_mismatch_rows(): void
    {
        $mysqlQuery = \Mockery::mock();
        $mongoConn = \Mockery::mock();
        $mongoCollection = \Mockery::mock();

        DB::shouldReceive('table')->once()->andReturn($mysqlQuery);
        $mysqlQuery->shouldReceive('orderByDesc')->once()->andReturnSelf();
        $mysqlQuery->shouldReceive('limit')->once()->with(50)->andReturnSelf();
        $mysqlQuery->shouldReceive('get')->once()->andReturn(new Collection([
            (object) ['uuid' => 'uuid-1', 'code' => 'ABC-001', 'views' => 10, 'downloads' => 3],
            (object) ['uuid' => 'uuid-2', 'code' => 'ABC-002', 'views' => 2, 'downloads' => 1],
        ]));

        DB::shouldReceive('connection')->twice()->with('mongodb')->andReturn($mongoConn);
        $mongoConn->shouldReceive('collection')->twice()->with('analytics_entity_totals')->andReturn($mongoCollection);
        $mongoCollection->shouldReceive('where')->times(6)->andReturnSelf();
        $mongoCollection->shouldReceive('first')->twice()->andReturn(
            ['view' => 10, 'download' => 3],
            ['view' => 1, 'download' => 1],
        );

        $service = new AnalyticsParityService;
        $result = $service->check(50);

        $this->assertSame(2, $result['checked']);
        $this->assertSame(1, $result['mismatches']);
        $this->assertSame('ABC-002', $result['rows'][0]['code']);
    }
}
