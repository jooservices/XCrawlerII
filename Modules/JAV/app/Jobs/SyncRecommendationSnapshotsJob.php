<?php

namespace Modules\JAV\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\JAV\Models\Jav;
use Modules\JAV\Services\RecommendationService;

class SyncRecommendationSnapshotsJob implements ShouldQueue
{
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public readonly ?int $javId = null,
        public readonly int $limit = 30
    ) {}

    public function handle(RecommendationService $recommendationService): void
    {
        if ($this->javId !== null) {
            $jav = Jav::query()->find($this->javId);
            if (! $jav) {
                return;
            }

            $recommendationService->syncSnapshotsForUsersByJav($jav, $this->limit);
        }
    }
}
