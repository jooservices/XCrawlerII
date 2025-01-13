<?php

namespace Modules\Udemy\Listeners;

use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\SyncCourseCurriculumItemsCompletedEvent;
use Modules\Udemy\Events\SyncMyCourseCompletedEvent;
use Modules\Udemy\Jobs\SyncCourseCurriculumItemsJob;
use Modules\Udemy\Models\UdemyCourse;

class MyCoursesSubscriber
{
    /**
     * Sync items after course created
     */
    final public function onSyncMyCourseCompleted(
        SyncMyCourseCompletedEvent $event
    ): void {
        if (!$event->syncCurriculumItems) {
            return;
        }

        SyncCourseCurriculumItemsJob::dispatch(
            $event->userToken,
            $event->udemyCourse
        );
    }

    final public function onSyncCourseCurriculumItemsCompleted(
        SyncCourseCurriculumItemsCompletedEvent $event
    ): void {
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
