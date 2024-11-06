<?php

namespace Modules\Udemy\app\Listeners;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\Courses\CourseCreatedEvent;
use Modules\Udemy\Events\Courses\UserCourseSyncCompletedEvent;
use Modules\Udemy\Events\CurriculumItemCreatedEvent;
use Modules\Udemy\Events\SyncCurriculumItemsSyncCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\UserCourseStudyCompleted;
use Modules\Udemy\Jobs\StudyCurriculumItem;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Notifications\CourseReadyForStudyNotification;
use Modules\Udemy\Notifications\CoursesSyncCompletedNotification;
use Modules\Udemy\Services\UdemyService;
use Throwable;

class UdemyCourseSubscriber
{
    public function handleCourseCreated(CourseCreatedEvent $event)
    {
        /**
         * Send global notifications to let users know course is created
         */
    }

    public function handleUserCourseSyncCompleted(UserCourseSyncCompletedEvent $event): void
    {
        SyncCurriculumItemsJob::dispatch(
            $event->user,
            $event->course,
        );
    }

    public function handleUdemyCoursesCompleted(SyncMyCoursesCompletedEvent $event): void
    {
        $event->userToken->notify(new CoursesSyncCompletedNotification(
            $event->userToken,
            $event->coursesEntity
        ));
    }

    public function handleCurriculumItemCreated(CurriculumItemCreatedEvent $event): void
    {
        /**
         * Nothing yet
         */
    }

    public function handleSyncCurriculumItemsSyncCompleted(SyncCurriculumItemsSyncCompletedEvent $event): void
    {
        $course = $event->udemyCourse;

        /**
         * @TODO Query with completion_ratio condition
         */
        $completionRatio = $event->userToken->courses()
            ->where('udemy_courses.id', $course->id)
            ->first()
            ->pivot->completion_ratio;

        /**
         * Only dispatch when course is not completed
         * and all items are synced
         */
        if (
            $event->curriculumItems->getCount() === $course->items->count()
            && $completionRatio < 100
        ) {
            CourseReadyForStudyEvent::dispatch(
                $event->userToken,
                $course
            );
        }
    }

    /**
     * @throws Throwable
     */
    public function handleCourseReadyForStudy(CourseReadyForStudyEvent $event): void
    {
        $event->userToken->notify(new CourseReadyForStudyNotification($event->udemyCourse));

        $items = $event->udemyCourse->items;

        if ($items->isEmpty()) {
            return;
        }

        $itemsBatch = [];

        $items->each(function ($item) use ($event, &$itemsBatch) {
            /**
             * @TODO Exclude non action lecture
             * - chapter
             */
            $itemsBatch[] = new StudyCurriculumItem($event->userToken, $item);
        });

        /**
         * Batch of items for study
         * Each item will also break down to chains
         */
        Bus::batch($itemsBatch)->before(function (Batch $batch) {
            /**
             * @TODO Dispatch event and notification to let user know their course started
             */
        })->progress(function (Batch $batch) {
            // A single job has completed successfully...
        })->then(function (Batch $batch) use ($event) {
            UserCourseStudyCompleted::dispatch(
                $event->userToken,
                $event->udemyCourse
            );
        })->catch(function (Batch $batch, Throwable $e) {
            // First batch job failure detected...
        })->finally(function (Batch $batch) {
            /**
             * @TODO Dispatch event and notification to let user know their course completed
             */
        })->name(
            $event->userToken->id . '.' . $event->udemyCourse->id
        )->onQueue(UdemyService::UDEMY_QUEUE_NAME)->dispatch();
    }

    public function subscribe(): array
    {
        return [
            CourseCreatedEvent::class, 'handleCourseCreated',
            UserCourseSyncCompletedEvent::class => 'handleUserCourseSyncCompleted',

            SyncMyCoursesCompletedEvent::class => 'handleUdemyCoursesCompleted',
            CurriculumItemCreatedEvent::class => 'handleCurriculumItemCreated',
            SyncCurriculumItemsSyncCompletedEvent::class => 'handleSyncCurriculumItemsSyncCompleted',

            CourseReadyForStudyEvent::class => 'handleCourseReadyForStudy',
        ];
    }
}
