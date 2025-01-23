<?php

namespace Modules\Udemy\Listeners;

use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\StudyCurriculumItemCompletedEvent;
use Modules\Udemy\Events\StudyInProgressEvent;
use Modules\Udemy\Services\StudyService;
use Throwable;

class CourseStudySubscriber
{
    /**
     * @throws Throwable
     * @throws BindingResolutionException
     */
    final public function onCourseReadyForStudy(CourseReadyForStudyEvent $event): void
    {
        app(StudyService::class)->study($event->userToken, $event->udemyCourse);
    }

    final public function onStudyInProgress(StudyInProgressEvent $event): void
    {
        /**
         * @TODO Notification for each progress
         */
    }

    /**
     * @throws BindingResolutionException
     */
    final public function onCurriculumItemCompleted(StudyCurriculumItemCompletedEvent $event): void
    {
        app(UdemySdk::class)
            ->setToken($event->userToken)
            ->me()
            ->completedLectures($event->curriculumItem);
    }

    final public function subscribe(): array
    {
        return [
            CourseReadyForStudyEvent::class => 'onCourseReadyForStudy',
            StudyInProgressEvent::class => 'onStudyInProgress',
            StudyCurriculumItemCompletedEvent::class => 'onCurriculumItemCompleted',
        ];
    }
}
