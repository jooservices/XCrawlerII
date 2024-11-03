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
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
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
    public function __construct(private string $token, private int $page = 1)
    {
        $this->onQueue(UdemyService::UDEMY_QUEUE_NAME);
        $this->user = UserToken::updateOrCreate([
            'token' => $this->token,
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(UdemySdk $sdk): void
    {
        $coursesEntity = $sdk->me()->subscribedCourses(
            $this->token,
            ['page' => $this->page]
        );

        if (!$coursesEntity) {
            return;
        }

        $coursesEntity->getResults()->each(function (CourseEntity $courseEntity) {
            $model = UdemyCourse::updateOrCreate(
                [
                    'id' => $courseEntity->getId(),
                    'url' => $courseEntity->getUrl(),
                ],
                $courseEntity->toArray()
            );

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
        });

        if ($coursesEntity->pages() > 1 && $coursesEntity->pages() > $this->page) {
            for ($index = 2; $index <= $coursesEntity->pages(); $index++) {
                SyncMyCoursesJob::dispatch($this->token, $index);
            }
        }

        SyncMyCoursesCompletedEvent::dispatch($this->user, $coursesEntity);
    }
}
