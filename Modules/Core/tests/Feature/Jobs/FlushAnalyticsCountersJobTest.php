<?php

namespace Modules\Core\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Log;
use Modules\Core\Jobs\FlushAnalyticsCountersJob;
use Modules\Core\Services\AnalyticsFlushService;
use Modules\Core\Tests\TestCase;

class FlushAnalyticsCountersJobTest extends TestCase
{
    public function test_job_runs_flush_service_and_no_warning_when_no_errors(): void
    {
        $service = \Mockery::mock(AnalyticsFlushService::class);
        $service->shouldReceive('flush')->once()->andReturn([
            'keys_processed' => 2,
            'errors' => 0,
        ]);

        Log::shouldReceive('warning')->never();

        $job = new FlushAnalyticsCountersJob;
        $job->handle($service);

        $this->assertTrue(true);
    }

    public function test_job_logs_warning_when_flush_has_errors(): void
    {
        $service = \Mockery::mock(AnalyticsFlushService::class);
        $service->shouldReceive('flush')->once()->andReturn([
            'keys_processed' => 3,
            'errors' => 1,
        ]);

        Log::shouldReceive('warning')
            ->once()
            ->with('Analytics flush completed with errors', [
                'keys_processed' => 3,
                'errors' => 1,
            ]);

        $job = new FlushAnalyticsCountersJob;
        $job->handle($service);

        $this->assertTrue(true);
    }
}
