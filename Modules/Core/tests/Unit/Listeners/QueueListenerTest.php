<?php

namespace Modules\Core\Tests\Unit\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Mockery;
use Modules\Core\Listeners\Queue\LogJobFailedListener;
use Modules\Core\Listeners\Queue\LogJobProcessedListener;
use Modules\Core\Listeners\Queue\LogJobStartedListener;
use Modules\Core\Services\JobTelemetryService;
use Modules\Core\Tests\TestCase;

class QueueListenerTest extends TestCase
{
    private JobTelemetryService $telemetryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->telemetryService = Mockery::mock(JobTelemetryService::class);
    }

    public function test_log_job_started_listener_delegates_to_telemetry_service(): void
    {
        $event = Mockery::mock(JobProcessing::class);
        $this->telemetryService->shouldReceive('recordStarted')
            ->once()
            ->with($event);

        $listener = new LogJobStartedListener($this->telemetryService);
        $listener->handle($event);
    }

    public function test_log_job_processed_listener_delegates_to_telemetry_service(): void
    {
        $event = Mockery::mock(JobProcessed::class);
        $this->telemetryService->shouldReceive('recordProcessed')
            ->once()
            ->with($event);

        $listener = new LogJobProcessedListener($this->telemetryService);
        $listener->handle($event);
    }

    public function test_log_job_failed_listener_delegates_to_telemetry_service(): void
    {
        $event = Mockery::mock(JobFailed::class);
        $this->telemetryService->shouldReceive('recordFailed')
            ->once()
            ->with($event);

        $listener = new LogJobFailedListener($this->telemetryService);
        $listener->handle($event);
    }
}
