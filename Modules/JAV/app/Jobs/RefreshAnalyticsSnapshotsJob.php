<?php

namespace Modules\JAV\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\JAV\Services\AnalyticsSnapshotService;

class RefreshAnalyticsSnapshotsJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 3600;

    /**
     * Execute the job.
     */
    public function handle(AnalyticsSnapshotService $analyticsSnapshotService): void
    {
        foreach ([7, 14, 30, 90] as $days) {
            $analyticsSnapshotService->getSnapshot($days, true, false);
        }
    }
}
