<?php

namespace Modules\Core\Tests\Unit\Services;

use Illuminate\Support\Facades\Redis;
use Modules\Core\Services\AnalyticsIngestService;
use Modules\Core\Tests\TestCase;

class AnalyticsIngestServiceTest extends TestCase
{
    public function test_ingest_writes_total_and_daily_counters(): void
    {
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:jav:movie:uuid-123', 'view', 1);
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:jav:movie:uuid-123', 'view:2026-02-19', 1);

        $service = new AnalyticsIngestService;
        $service->ingest([
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'uuid-123',
            'action' => 'view',
            'value' => 1,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }

    public function test_ingest_uses_custom_value(): void
    {
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:jav:movie:uuid-123', 'download', 5);
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:jav:movie:uuid-123', 'download:2026-02-19', 5);

        $service = new AnalyticsIngestService;
        $service->ingest([
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'uuid-123',
            'action' => 'download',
            'value' => 5,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }

    public function test_ingest_defaults_value_to_1(): void
    {
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:jav:movie:uuid-123', 'view', 1);
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:jav:movie:uuid-123', 'view:2026-02-19', 1);

        $service = new AnalyticsIngestService;
        $service->ingest([
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'uuid-123',
            'action' => 'view',
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }

    public function test_ingest_uses_configured_redis_prefix(): void
    {
        config(['analytics.redis_prefix' => 'analytics:v2']);

        Redis::shouldReceive('hincrby')
            ->once()
            ->with('analytics:v2:jav:movie:uuid-123', 'view', 2);
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('analytics:v2:jav:movie:uuid-123', 'view:2026-02-19', 2);

        $service = new AnalyticsIngestService;
        $service->ingest([
            'domain' => 'jav',
            'entity_type' => 'movie',
            'entity_id' => 'uuid-123',
            'action' => 'view',
            'value' => 2,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }
}
