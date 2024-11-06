<?php

namespace Modules\Udemy\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\UserHaveNoCoursesSubscribedEvent;
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
    public function handle(UdemySdk $sdk): void
    {
        $coursesEntity = $sdk
            ->me()
            ->subscribedCourses($this->userToken->token, ['page' => $this->page]);

        if (!$coursesEntity) {
            UserHaveNoCoursesSubscribedEvent::dispatch($this->userToken);

            return;
        }

        $repository = app(UdemyCourseRepository::class);
        $userTokenRepository = app(UserTokenRepository::class);

        $coursesEntity->getResults()->each(
            function (CourseEntity $courseEntity) use ($repository, $userTokenRepository) {
                $userTokenRepository->syncCourse(
                    $this->userToken,
                    $repository->createFromEntity($courseEntity),
                    $courseEntity->completion_ratio,
                    Carbon::parse($courseEntity->enrollment_time)
                );
            }
        );

        if (
            $this->page === 1 // Only process pages for first page
            && $coursesEntity->pages() > 1
            && $coursesEntity->pages() > $this->page
        ) {
            for ($index = 2; $index <= $coursesEntity->pages(); $index++) {
                SyncMyCoursesJob::dispatch($this->userToken, $index);
            }
        }

        SyncMyCoursesCompletedEvent::dispatch($this->userToken, $coursesEntity);
    }
}
