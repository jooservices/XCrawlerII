<?php

namespace Modules\Udemy\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Events\Courses\SyncMyCourseProcessingEvent;
use Modules\Udemy\Events\Courses\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\UdemyService;
use Throwable;

class SyncMyCoursesJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly UserToken $userToken,
        private readonly int $page = 1
    ) {
        $this->onQueue(UdemyService::UDEMY_QUEUE_NAME);
    }

    /**
     * Execute the job.
     *
     * @throws \Exception
     */
    public function handle(): void
    {
        $coursesEntity = app(UdemyService::class)->syncMyCourse(
            $this->userToken,
            ['page' => $this->page]
        );

        if (
            $this->page === 1 // Only process pages for first page
            && $coursesEntity->pages() > 1
            && $coursesEntity->pages() > $this->page
        ) {
            $batch = [];
            for ($index = 2; $index <= $coursesEntity->pages(); $index++) {
                $batch[] = new SyncMyCoursesJob($this->userToken, $index);
            }

            $userToken = $this->userToken;
            Bus::batch($batch)->before(function (Batch $batch) {
                // The batch has been created but no jobs have been added...
            })->progress(function (Batch $batch) {
                SyncMyCourseProcessingEvent::dispatch();
            })->then(function (Batch $batch) use ($userToken) {
                SyncMyCoursesCompletedEvent::dispatch($userToken);
            })->catch(function (Batch $batch, Throwable $e) {
                // First batch job failure detected...
            })->finally(function (Batch $batch) {
                // The batch has finished executing...
            })->name('Sync my courses')->onQueue(UdemyService::UDEMY_QUEUE_NAME)->dispatch();
        }
    }
}
