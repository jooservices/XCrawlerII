<?php

namespace Modules\Udemy\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Udemy\Events\CurriculumItemCreatedEvent;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\Entities\CurriculumItemEntity;
use Modules\Udemy\Services\Client\UdemySdk;
use Modules\Udemy\Services\UdemyService;

class SyncCurriculumItemsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public UserToken $user,
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
            $this->user->token,
            $this->course->id,
            [
                'page' => $this->page,
            ]
        );
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
                    $this->user,
                    $entities,
                    $model
                );
            }
        );

        if ($entities->pages() > 1 && $entities->pages() > $this->page) {
            for ($index = 2; $index <= $entities->pages(); $index++) {
                SyncCurriculumItemsJob::dispatch($this->user, $this->course, $index);
            }
        }
    }
}
