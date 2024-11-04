<?php

namespace Modules\Udemy\app\Listeners;

use Illuminate\Events\Dispatcher;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\CurriculumItemCreatedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\UdemyCourseCreatedEvent;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Notifications\CourseReadyForStudyNotification;
use Modules\Udemy\Notifications\CoursesSyncCompletedNotification;
use Modules\Udemy\Services\UdemyService;

class UdemyCourseSubscriber
{
    public function handleUdemyCourseCreated(UdemyCourseCreatedEvent $event): void
    {
        SyncCurriculumItemsJob::dispatch(
            $event->user,
            $event->course,
        );
    }

    public function handleUdemyCoursesCompleted(SyncMyCoursesCompletedEvent $event)
    {
        $event->userToken->notify(new CoursesSyncCompletedNotification(
            $event->userToken,
            $event->coursesEntity
        ));
    }

    public function handleCurriculumItemCreated(CurriculumItemCreatedEvent $event): void
    {
        $course = $event->curriculumItem->course;
        $completionRatio = $event->userToken->courses()
            ->where('udemy_courses.id', $course->id)
            ->first()
            ->pivot->completion_ratio;

        /**
         * Only dispatch when course is not completed
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

    public function handleCourseReadyForStudy(CourseReadyForStudyEvent $event): void
    {
        $event->userToken->notify(new CourseReadyForStudyNotification($event->udemyCourse));

        $items = $event->udemyCourse->items;

        if ($items->isEmpty()) {
            return;
        }

        $service = app(UdemyService::class);
        $items->each(function ($item) use ($event, $service) {
            $service->completeCurriculum(
                $event->userToken,
                $item,
            );
        });
        /**
         * @TODO Handle completed
         */
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            UdemyCourseCreatedEvent::class => 'handleUdemyCourseCreated',
            SyncMyCoursesCompletedEvent::class => 'handleUdemyCoursesCompleted',
            CurriculumItemCreatedEvent::class => 'handleCurriculumItemCreated',
            CourseReadyForStudyEvent::class => 'handleCourseReadyForStudy',
        ];
    }
}
