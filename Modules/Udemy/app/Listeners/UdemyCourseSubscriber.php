<?php

namespace Modules\Udemy\app\Listeners;

use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\Courses\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\Courses\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\CurriculumItemCreatedEvent;
use Modules\Udemy\Events\CurriculumItems\SyncCurriculumItemsCompletedEvent;
use Modules\Udemy\Events\UserCourseStudyCompleted;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Notifications\CoursesSyncCompletedNotification;
use Modules\Udemy\Notifications\StudyCourseCompletedEvent;
use Modules\Udemy\Services\UdemyService;
use Throwable;

class UdemyCourseSubscriber
{
    public function handleUserCourseSyncCompleted(SyncMyCourseCompletedEvent $event): void
    {
        SyncCurriculumItemsJob::dispatch(
            $event->user,
            $event->course,
        );
    }

    public function handleUdemyCoursesCompleted(SyncMyCoursesCompletedEvent $event): void
    {
        if (config('udemy.notifications.enabled', false)) {
            $event->userToken->notify(new CoursesSyncCompletedNotification($event->userToken));
        }
    }

    public function handleCurriculumItemCreated(CurriculumItemCreatedEvent $event): void
    {
        /**
         * Nothing yet
         */
    }

    public function handleSyncCurriculumItemsSyncCompleted(SyncCurriculumItemsCompletedEvent $event): void
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
        app(UdemyService::class)->completeCurriculumItems($event->userToken, $event->udemyCourse);
    }

    public function handleUserCourseCompleted(UserCourseStudyCompleted $event): void
    {
        $event->userToken->notify(new StudyCourseCompletedEvent(
            $event->userToken,
            $event->udemyCourse
        ));
    }

    public function subscribe(): array
    {
        return [
            SyncMyCourseCompletedEvent::class => 'handleUserCourseSyncCompleted',

            SyncMyCoursesCompletedEvent::class => 'handleUdemyCoursesCompleted',
            CurriculumItemCreatedEvent::class => 'handleCurriculumItemCreated',
            SyncCurriculumItemsCompletedEvent::class => 'handleSyncCurriculumItemsSyncCompleted',

            CourseReadyForStudyEvent::class => 'handleCourseReadyForStudy',

            UserCourseStudyCompleted::class => 'handleUserCourseCompleted',
        ];
    }
}
