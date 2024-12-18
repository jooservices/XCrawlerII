<?php

namespace Modules\Udemy\Listeners;

use Modules\Udemy\Events\CourseCreatedEvent;
use Modules\Udemy\Events\CourseCurriculumItemAttachedEvent;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\SyncCourseCurriculumItemsCompletedEvent;
use Modules\Udemy\Events\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\SyncMyCourseFailedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesProgressingEvent;
use Modules\Udemy\Jobs\SyncCourseCurriculumItemJob;
use Modules\Udemy\Jobs\SyncCourseCurriculumItemsJob;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Notifications\SyncMyCoursesCompletedNotif;

class MyCoursesSubscriber
{
    /**
     * Sync items after course created
     * @param SyncMyCourseCompletedEvent $event
     * @return void
     */
    final public function onSyncMyCourseCompleted(SyncMyCourseCompletedEvent $event): void
    {
        $userToken = $event->userToken;
        $udemyCourse = $event->udemyCourse;

        SyncCourseCurriculumItemsJob::dispatch($userToken, $udemyCourse);
    }

    public function onSyncCourseCurriculumItemsCompleted(SyncCourseCurriculumItemsCompletedEvent $event)
    {
        /**
         * @var UdemyCourse $item
         */
        $item = $event->userToken->courses()
            ->where('udemy_course_id', $event->udemyCourse->id)
            ->first();

        if ($item->pivot->completion_ratio < 100) {
            /**
             * Ready for study
             */
            CourseReadyForStudyEvent::dispatch(
                $event->userToken,
                $item
            );
        }
    }

    final public function subscribe(): array
    {
        return [
            SyncMyCourseCompletedEvent::class => 'onSyncMyCourseCompleted',
        ];
    }
}
