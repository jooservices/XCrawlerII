<?php

namespace Modules\Core\Listeners\Queue;

use Illuminate\Queue\Events\JobProcessing;
use Modules\Core\Services\JobTelemetryService;

class LogJobStartedListener
{
    public function __construct(private readonly JobTelemetryService $telemetryService) {}

    public function handle(JobProcessing $event): void
    {
        $this->telemetryService->recordStarted($event);
    }
}
