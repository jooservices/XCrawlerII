<?php

namespace Modules\Udemy\Listeners;

use Modules\Udemy\Events\CourseCreatedEvent;
use Modules\Udemy\Events\CourseCurriculumItemAttachedEvent;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\SyncCourseCurriculumItemsCompletedEvent;
use Modules\Udemy\Events\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesFinishedEvent;
use Modules\Udemy\Events\SyncMyCoursesProgressingEvent;
use Modules\Udemy\Events\UserAttachedCourseEvent;
use Modules\Udemy\Events\UserSyncMyCourseFailedEvent;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Notifications\SyncMyCoursesCompletedNotif;
use Modules\Udemy\Services\CourseService;

class MyCoursesSubscriber
{
    public function onUserSyncMyCourseFailed(UserSyncMyCourseFailedEvent $event)
    {
        /**
         * Send notification
         */
    }

    public function onCourseCreated(CourseCreatedEvent $event)
    {
        /**
         * Send notification
         */
    }

    public function onUserAttachedCourse(UserAttachedCourseEvent $event)
    {
    }

    public function onSyncMyCourseCompleted(SyncMyCourseCompletedEvent $event)
    {
        echo __FUNCTION__ . "\n";
    }

    public function onSyncMyCoursesProgressing(SyncMyCoursesProgressingEvent $event)
    {
        echo __FUNCTION__ . "\n";
    }

    public function onSyncMyCoursesCompleted(SyncMyCoursesCompletedEvent $event): void
    {
        echo __FUNCTION__ . "\n";
    }

    public function onSyncMyCoursesFinished(SyncMyCoursesFinishedEvent $event): void
    {
        if (config('udemy.notifications.enabled', false)) {
            $event->userToken->notify(new SyncMyCoursesCompletedNotif($event->userToken));
        }

        /**
         * Process not completed courses
         */
        $event->userToken->notCompletedCourses()->each(function ($course) use ($event) {
            app(CourseService::class)->syncCurriculumItems($event->userToken, $course);
        });
    }

    public function onCourseCurriculumItemAttached(CourseCurriculumItemAttachedEvent $event)
    {
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

    public function subscribe(): array
    {
        return [
            UserSyncMyCourseFailedEvent::class => 'onUserSyncMyCourseFailed',
            CourseCreatedEvent::class => 'onCourseCreated',
            UserAttachedCourseEvent::class => 'onUserAttachedCourse',
            SyncMyCourseCompletedEvent::class => 'onSyncMyCourseCompleted',
            SyncMyCoursesProgressingEvent::class => 'onSyncMyCoursesProgressing',
            SyncMyCoursesCompletedEvent::class => 'onSyncMyCoursesCompleted',
            SyncMyCoursesFinishedEvent::class => 'onSyncMyCoursesFinished',

            CourseCurriculumItemAttachedEvent::class => 'onCourseCurriculumItemAttached',
            SyncCourseCurriculumItemsCompletedEvent::class => 'onSyncCourseCurriculumItemsCompleted',
        ];
    }
}
