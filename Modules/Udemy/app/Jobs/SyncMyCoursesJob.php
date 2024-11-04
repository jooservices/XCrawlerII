<?php

namespace Modules\Udemy\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\UdemyCourseCreatedEvent;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Repositories\UdemyCourseRepository;
use Modules\Udemy\Repositories\UserTokenRepository;
use Modules\Udemy\Services\Client\Entities\CourseEntity;
use Modules\Udemy\Services\Client\UdemySdk;
use Modules\Udemy\Services\UdemyService;

class SyncMyCoursesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    private UserToken $user;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly string $token,
        private readonly int $page = 1
    ) {
        $this->onQueue(UdemyService::UDEMY_QUEUE_NAME);
        $this->user = app(UserTokenRepository::class)
            ->create(['token' => $this->token]);
    }

    /**
     * Execute the job.
     * @throws \Exception
     */
    public function handle(UdemySdk $sdk): void
    {
        $coursesEntity = $sdk
            ->me()
            ->subscribedCourses($this->token, ['page' => $this->page]);

        if (!$coursesEntity) {
            return;
        }

        $repository = app(UdemyCourseRepository::class);
        $coursesEntity->getResults()->each(
            function (CourseEntity $courseEntity) use ($repository) {
                $model = $repository->create($courseEntity);

                $this->user->courses()->syncWithoutDetaching(
                    [
                        $model->id => [
                            'completion_ratio' => $courseEntity->completion_ratio,
                            'enrollment_time' => Carbon::parse($courseEntity->enrollment_time),
                        ],
                    ]
                );

                if ($model->wasRecentlyCreated) {
                    UdemyCourseCreatedEvent::dispatch($this->user, $model);
                }
            }
        );

        if ($coursesEntity->pages() > 1 && $coursesEntity->pages() > $this->page) {
            for ($index = 2; $index <= $coursesEntity->pages(); $index++) {
                SyncMyCoursesJob::dispatch($this->token, $index);
            }
        }

        SyncMyCoursesCompletedEvent::dispatch($this->user, $coursesEntity);
    }
}
