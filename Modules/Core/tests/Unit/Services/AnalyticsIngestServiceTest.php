<?php

namespace Modules\Core\Tests\Unit\Services;

use Illuminate\Support\Facades\Redis;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Services\AnalyticsIngestService;
use Modules\Core\Tests\TestCase;

class AnalyticsIngestServiceTest extends TestCase
{
    public function test_ingest_writes_total_and_daily_counters(): void
    {
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', 172800, 'NX')
            ->andReturn(true);

        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:'.AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$entityId}", AnalyticsAction::View->value, 1);
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:'.AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$entityId}", AnalyticsAction::View->value.':2026-02-19', 1);

        $service = new AnalyticsIngestService;
        $service->ingest([
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'value' => 1,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }

    public function test_ingest_uses_custom_value(): void
    {
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', 172800, 'NX')
            ->andReturn(true);

        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:'.AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$entityId}", AnalyticsAction::Download->value, 5);
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:'.AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$entityId}", AnalyticsAction::Download->value.':2026-02-19', 5);

        $service = new AnalyticsIngestService;
        $service->ingest([
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::Download->value,
            'value' => 5,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }

    public function test_ingest_defaults_value_to_1(): void
    {
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', 172800, 'NX')
            ->andReturn(true);

        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:'.AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$entityId}", AnalyticsAction::View->value, 1);
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('anl:counters:'.AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$entityId}", AnalyticsAction::View->value.':2026-02-19', 1);

        $service = new AnalyticsIngestService;
        $service->ingest([
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }

    public function test_ingest_uses_configured_redis_prefix(): void
    {
        config(['analytics.redis_prefix' => 'analytics:v2']);
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();

        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', 172800, 'NX')
            ->andReturn(true);

        Redis::shouldReceive('hincrby')
            ->once()
            ->with('analytics:v2:'.AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$entityId}", AnalyticsAction::View->value, 2);
        Redis::shouldReceive('hincrby')
            ->once()
            ->with('analytics:v2:'.AnalyticsDomain::Jav->value.':'.AnalyticsEntityType::Movie->value.":{$entityId}", AnalyticsAction::View->value.':2026-02-19', 2);

        $service = new AnalyticsIngestService;
        $service->ingest([
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'value' => 2,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }

    public function test_ingest_skips_counter_writes_for_duplicate_event_id(): void
    {
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', 172800, 'NX')
            ->andReturn(false);
        Redis::shouldReceive('hincrby')->never();

        $service = new AnalyticsIngestService;
        $service->ingest([
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'value' => 1,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }

    public function test_dedupe_ttl_is_48_hours(): void
    {
        $eventId = $this->faker->uuid();
        $entityId = $this->faker->uuid();
        $ttlSeconds = 48 * 60 * 60; // 172800
        Redis::shouldReceive('set')
            ->once()
            ->with("anl:evt:{$eventId}", '1', 'EX', $ttlSeconds, 'NX')
            ->andReturn(true);
        Redis::shouldReceive('hincrby')->twice();

        $service = new AnalyticsIngestService;
        $service->ingest([
            'event_id' => $eventId,
            'domain' => AnalyticsDomain::Jav->value,
            'entity_type' => AnalyticsEntityType::Movie->value,
            'entity_id' => $entityId,
            'action' => AnalyticsAction::View->value,
            'occurred_at' => '2026-02-19T10:00:00Z',
        ]);
    }
}
