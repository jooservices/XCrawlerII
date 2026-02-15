<?php

namespace Modules\Core\Listeners\Queue;

use Illuminate\Queue\Events\JobProcessed;
use Modules\Core\Services\JobTelemetryService;

class LogJobProcessedListener
{
    public function __construct(private readonly JobTelemetryService $telemetryService) {}

    public function handle(JobProcessed $event): void
    {
        $this->telemetryService->recordProcessed($event);
    }
}
