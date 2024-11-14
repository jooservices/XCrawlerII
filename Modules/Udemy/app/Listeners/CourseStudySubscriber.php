<?php

namespace Modules\Udemy\Listeners;

use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\StudyInProgressEvent;
use Modules\Udemy\Services\StudyService;
use Throwable;

class CourseStudySubscriber
{
    /**
     * @throws Throwable
     * @throws BindingResolutionException
     */
    public function onCourseReadyForStudy(CourseReadyForStudyEvent $event): void
    {
        app(StudyService::class)->study($event->userToken, $event->udemyCourse);
    }

    public function onStudyInProgress(StudyInProgressEvent $event)
    {
        /**
         * @TODO Notification for each progress
         */
    }

    public function subscribe(): array
    {
        return [
            CourseReadyForStudyEvent::class => 'onCourseReadyForStudy',
            StudyInProgressEvent::class => 'onStudyInProgress',
        ];
    }
}
