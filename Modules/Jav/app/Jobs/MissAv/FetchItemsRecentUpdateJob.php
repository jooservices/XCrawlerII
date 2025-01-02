<?php

namespace Modules\Jav\Jobs\MissAv;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use JsonException;
use Modules\Jav\Services\MissAv\MissAvService;
use Modules\Jav\Services\Onejav\OnejavService;

class FetchItemsRecentUpdateJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $page
    ) {
        $this->onQueue(OnejavService::ONEJAV_QUEUE_NAME);
    }

    /**
     * Execute the job.
     *
     * @throws JsonException
     */
    final public function handle(MissAvService $service): void
    {
        $service->recentUpdate($this->page);
    }
}
