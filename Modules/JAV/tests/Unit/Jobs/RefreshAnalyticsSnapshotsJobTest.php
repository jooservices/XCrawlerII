<?php

namespace Modules\JAV\Tests\Unit\Jobs;

use Mockery;
use Modules\JAV\Jobs\RefreshAnalyticsSnapshotsJob;
use Modules\JAV\Services\AnalyticsSnapshotService;
use Modules\JAV\Tests\TestCase;

class RefreshAnalyticsSnapshotsJobTest extends TestCase
{
    public function test_handle_refreshes_all_supported_day_windows(): void
    {
        $service = Mockery::mock(AnalyticsSnapshotService::class);
        $service->shouldReceive('getSnapshot')->once()->with(7, true, false)->andReturn([]);
        $service->shouldReceive('getSnapshot')->once()->with(14, true, false)->andReturn([]);
        $service->shouldReceive('getSnapshot')->once()->with(30, true, false)->andReturn([]);
        $service->shouldReceive('getSnapshot')->once()->with(90, true, false)->andReturn([]);

        $job = new RefreshAnalyticsSnapshotsJob();
        $job->handle($service);

        $this->assertTrue(true);
    }
}
