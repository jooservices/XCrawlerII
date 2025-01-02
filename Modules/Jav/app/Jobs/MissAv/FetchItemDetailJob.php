<?php

namespace Modules\Jav\Jobs\MissAv;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Jav\Models\MissAvReference;
use Modules\Jav\Services\MissAv\MissAvService;
use Modules\Jav\Services\Onejav\OnejavService;

class FetchItemDetailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public MissAvReference $model
    ) {
        $this->onQueue(OnejavService::ONEJAV_QUEUE_NAME);
    }

    /**
     * Execute the job.
     */
    final public function handle(MissAvService $service): void
    {
        $service->updateDetail($this->model);

        dd($this->model);
    }
}
