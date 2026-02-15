<?php

namespace Modules\Core\Listeners\Queue;

use Illuminate\Queue\Events\JobFailed;
use Modules\Core\Services\JobTelemetryService;

class LogJobFailedListener
{
    public function __construct(private readonly JobTelemetryService $telemetryService) {}

    public function handle(JobFailed $event): void
    {
        $this->telemetryService->recordFailed($event);
    }
}
