<?php

namespace Modules\Udemy\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Events\CurriculumItemCreatedEvent;
use Modules\Udemy\Events\CurriculumItems\SyncCurriculumItemsCompletedEvent;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\Entities\CurriculumItemEntity;
use Modules\Udemy\Services\Client\UdemySdk;
use Modules\Udemy\Services\UdemyService;

class SyncCurriculumItemsJob implements ShouldQueue
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
        public UdemyCourse $course,
        public int $page = 1
    ) {
        $this->onQueue(UdemyService::UDEMY_QUEUE_NAME);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $entities = app(UdemySdk::class)->courses()->subscriberCurriculumItems(
            $this->userToken->token,
            $this->course->id,
            [
                'page' => $this->page,
            ]
        );

        if ($this->course->items()->count() === $entities->getCount()) {
            SyncCurriculumItemsCompletedEvent::dispatch(
                $this->userToken,
                $this->course,
                $entities
            );

            return;
        }
        /**
         * Call progress to get studied items
         */
        $entities->getResults()->each(
            function (CurriculumItemEntity $curriculumItemEntity) use ($entities) {
                /**
                 * @TODO Move to repository
                 */
                $model = $this->course->items()->updateOrCreate(
                    [
                        'id' => $curriculumItemEntity->getId(),
                    ],
                    $curriculumItemEntity->toArray()
                );

                CurriculumItemCreatedEvent::dispatch(
                    $this->userToken,
                    $entities,
                    $model
                );
            }
        );

        if (
            $this->page === 1
            && $entities->pages() > 1
            && $entities->pages() > $this->page
        ) {
            $batch = [];

            for ($index = 2; $index <= $entities->pages(); $index++) {
                $batch[] = new SyncCurriculumItemsJob($this->userToken, $this->course, $index);
            }

            Bus::chain($batch)
                ->onQueue(UdemyService::UDEMY_QUEUE_NAME)->dispatch();
        }

        SyncCurriculumItemsCompletedEvent::dispatch(
            $this->userToken,
            $this->course,
            $entities
        );
    }
}
