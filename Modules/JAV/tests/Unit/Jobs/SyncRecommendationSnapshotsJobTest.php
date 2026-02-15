<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Mockery;
use Modules\JAV\Jobs\SyncRecommendationSnapshotsJob;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\RecommendationService;
use Modules\JAV\Tests\TestCase;

class SyncRecommendationSnapshotsJobTest extends TestCase
{
    public function test_handle_returns_early_when_jav_id_is_missing(): void
    {
        $service = Mockery::mock(RecommendationService::class);
        $service->shouldNotReceive('syncSnapshotsForUsersByJav');

        $job = new SyncRecommendationSnapshotsJob(null, 12);
        $job->handle($service);

        $this->assertTrue(true);
    }

    public function test_handle_returns_early_when_jav_does_not_exist(): void
    {
        $service = Mockery::mock(RecommendationService::class);
        $service->shouldNotReceive('syncSnapshotsForUsersByJav');

        $job = new SyncRecommendationSnapshotsJob(999999, 12);
        $job->handle($service);

        $this->assertTrue(true);
    }

    public function test_handle_syncs_for_existing_jav(): void
    {
        $jav = Jav::factory()->create();

        $service = Mockery::mock(RecommendationService::class);
        $service->shouldReceive('syncSnapshotsForUsersByJav')
            ->once()
            ->withArgs(function (Jav $jobJav, int $limit) use ($jav): bool {
                return $jobJav->id === $jav->id && $limit === 25;
            })
            ->andReturn(1);

        $job = new SyncRecommendationSnapshotsJob($jav->id, 25);
        $job->handle($service);

        $this->assertTrue(true);
    }
}
