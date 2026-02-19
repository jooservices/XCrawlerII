<?php

namespace Modules\JAV\Tests\Unit\Services;

use Mockery;
use Modules\Core\Enums\AnalyticsAction;
use Modules\Core\Enums\AnalyticsDomain;
use Modules\Core\Enums\AnalyticsEntityType;
use Modules\Core\Services\AnalyticsIngestService;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\JavAnalyticsTrackerService;
use Modules\JAV\Tests\TestCase;

class JavAnalyticsTrackerServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_track_download_calls_ingest_with_correct_payload(): void
    {
        $jav = Jav::factory()->create([
            'uuid' => '550e8400-e29b-41d4-a716-446655440000',
            'source' => 'onejav',
        ]);

        $ingestService = Mockery::mock(AnalyticsIngestService::class);
        $ingestService->shouldReceive('ingest')
            ->once()
            ->withArgs(function (array $event, $userId): bool {
                return $event['domain'] === AnalyticsDomain::Jav->value
                    && $event['entity_type'] === AnalyticsEntityType::Movie->value
                    && $event['entity_id'] === '550e8400-e29b-41d4-a716-446655440000'
                    && $event['action'] === AnalyticsAction::Download->value
                    && $event['value'] === 1
                    && isset($event['event_id'])
                    && isset($event['occurred_at']);
            });

        $tracker = new JavAnalyticsTrackerService($ingestService);
        $tracker->trackDownload($jav);
    }

    public function test_track_download_passes_jav_uuid_as_entity_id(): void
    {
        $jav = Jav::factory()->create([
            'uuid' => '7c9e6679-7425-40de-944b-e07fc1f90ae7',
            'source' => 'onejav',
        ]);

        $ingestService = Mockery::mock(AnalyticsIngestService::class);
        $ingestService->shouldReceive('ingest')
            ->once()
            ->withArgs(function (array $event) use ($jav): bool {
                return $event['entity_id'] === (string) $jav->uuid;
            });

        $tracker = new JavAnalyticsTrackerService($ingestService);
        $tracker->trackDownload($jav);
    }
}
