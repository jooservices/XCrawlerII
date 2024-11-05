<?php

namespace Modules\Udemy\app\Listeners;

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\CurriculumItemCreatedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\UserCourseStudyCompleted;
use Modules\Udemy\Events\UserCourseSyncCompleted;
use Modules\Udemy\Jobs\StudyCurriculumItem;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Notifications\CourseReadyForStudyNotification;
use Modules\Udemy\Notifications\CoursesSyncCompletedNotification;
use Throwable;

class UdemyCourseSubscriber
{
    public function handleUserCourseSyncCompleted(UserCourseSyncCompleted $event): void
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
        $course = $event->curriculumItem->course;
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
            $event->courseCurriculumItemsEntity->getCount() === $course->items->count()
            && $completionRatio < 100
        ) {
            CourseReadyForStudyEvent::dispatch(
                $event->userToken,
                $event->curriculumItem->course,
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
        )->dispatch();
    }

    public function subscribe(): array
    {
        return [
            UserCourseSyncCompleted::class => 'handleUserCourseSyncCompleted',

            SyncMyCoursesCompletedEvent::class => 'handleUdemyCoursesCompleted',
            CurriculumItemCreatedEvent::class => 'handleCurriculumItemCreated',
            CourseReadyForStudyEvent::class => 'handleCourseReadyForStudy',
        ];
    }
}
