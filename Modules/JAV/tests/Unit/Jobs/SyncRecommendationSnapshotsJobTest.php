<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Modules\JAV\Jobs\SyncRecommendationSnapshotsJob;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\RecommendationService;
use Modules\JAV\Tests\TestCase;

class SyncRecommendationSnapshotsJobTest extends TestCase
{
    public function test_handle_returns_early_when_jav_id_is_missing(): void
    {
        $service = new class extends RecommendationService
        {
            public int $calls = 0;

            public function syncSnapshotsForUsersByJav(Jav $jav, int $limit = 30): int
            {
                $this->calls++;

                return 0;
            }
        };

        $job = new SyncRecommendationSnapshotsJob(null, 12);
        $job->handle($service);

        $this->assertSame(0, $service->calls);
    }

    public function test_handle_returns_early_when_jav_does_not_exist(): void
    {
        $service = new class extends RecommendationService
        {
            public int $calls = 0;

            public function syncSnapshotsForUsersByJav(Jav $jav, int $limit = 30): int
            {
                $this->calls++;

                return 0;
            }
        };

        $job = new SyncRecommendationSnapshotsJob(999999, 12);
        $job->handle($service);

        $this->assertSame(0, $service->calls);
    }

    public function test_handle_syncs_for_existing_jav(): void
    {
        $jav = Jav::factory()->create();

        $service = new class extends RecommendationService
        {
            public ?int $lastJavId = null;

            public ?int $lastLimit = null;

            public function syncSnapshotsForUsersByJav(Jav $jav, int $limit = 30): int
            {
                $this->lastJavId = $jav->id;
                $this->lastLimit = $limit;

                return 1;
            }
        };

        $job = new SyncRecommendationSnapshotsJob($jav->id, 25);
        $job->handle($service);

        $this->assertSame($jav->id, $service->lastJavId);
        $this->assertSame(25, $service->lastLimit);
    }
}
