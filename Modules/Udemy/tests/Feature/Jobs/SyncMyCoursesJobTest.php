<?php

namespace Modules\Udemy\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Event;
use Modules\Udemy\Events\Courses\CourseCreatedEvent;
use Modules\Udemy\Events\Courses\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\Courses\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Jobs\SyncMyCoursesJob;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;

/**
 * @TODO
 * - Test when no courses found
 * - Test with multi pages
 */
class SyncMyCoursesJobTest extends TestCase
{
    public function testFlowWithHappyCase()
    {
        $totalCourses = 90;

        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
            SyncMyCoursesCompletedEvent::class,
        ]);

        /**
         * @var UserToken $userToken
         */
        $userToken = UserToken::factory()
            ->create();

        SyncMyCoursesJob::dispatch($userToken);
//
//        Event::assertDispatchedTimes(CourseCreatedEvent::class, $totalCourses);
//        $this->assertDatabaseCount('udemy_courses', $totalCourses);
//        $this->assertCount($totalCourses, $userToken->refresh()->courses);
//        Event::assertDispatchedTimes(UserCourseSyncCompletedEvent::class, $totalCourses);
//
//        Event::assertDispatched(SyncMyCoursesCompletedEvent::class);
    }
}
