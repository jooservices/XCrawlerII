<?php

namespace Modules\Udemy\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\UdemyService;

class SyncMyCourseJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public UserToken $userToken,
        public int $page,
        public bool $syncCurriculumItems = true
    ) {
        $this->onQueue(UdemyService::UDEMY_QUEUE_NAME);
    }

    /**
     * Execute the job.
     *
     * @throws BindingResolutionException
     */
    final public function handle(UdemyService $service): void
    {
        $service->syncMyCourse(
            $this->userToken,
            ['page' => $this->page],
            $this->syncCurriculumItems
        );
    }
}
