<?php

namespace Modules\Udemy\Tests\Unit\Events;

use Illuminate\Bus\PendingBatch;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\UserCourseStudyCompleted;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\CourseReadyForStudyNotification;
use Modules\Udemy\Tests\TestCase;

class CourseReadyForStudyTest extends TestCase
{
    /**
     * @throws \Exception
     */
    public function testCourseReadyForStudy()
    {
        Bus::fake();
        Notification::fake();
        Event::fake([
            UserCourseStudyCompleted::class
        ]);

        $userToken = UserToken::factory()
            ->withCourse()
            ->create();

        $course = $userToken->courses->first();
        $course->items()->save(CurriculumItem::factory()->create());

        /**
         * Because there is no items it'll process nothing
         */
        CourseReadyForStudyEvent::dispatch($userToken, $course);

        Notification::assertSentTo(
            $userToken,
            CourseReadyForStudyNotification::class
        );

        Bus::assertBatched(function (PendingBatch $batch) use ($userToken, $course) {
            return $batch->name === $userToken->id . '.' . $course->id
                && $batch->jobs->count() === $course->items->count();
        });
    }
}
