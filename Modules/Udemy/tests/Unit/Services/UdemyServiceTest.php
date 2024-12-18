<?php

namespace Modules\Udemy\Tests\Unit\Services;

use Illuminate\Support\Facades\Event;
use Modules\Udemy\Events\CourseCreatedEvent;
use Modules\Udemy\Events\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\UdemyService;
use Modules\Udemy\Tests\TestCase;

class UdemyServiceTest extends TestCase
{
    final public function testSyncMyCourse(): void
    {
        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
        ]);

        $userToken = UserToken::factory()->create([
            'token' => 'testing',
        ]);

        $service = app(UdemyService::class);
        $coursesDto = $service->syncMyCourses($userToken);

        $this->assertEquals(1, $coursesDto->pages());

        Event::assertDispatchedTimes(
            CourseCreatedEvent::class,
            $coursesDto->getCount()
        );
        Event::assertDispatchedTimes(
            SyncMyCourseCompletedEvent::class,
            $coursesDto->getCount()
        );

        $this->assertDatabaseCount('udemy_courses', $coursesDto->getCount());
    }

    final public function testSyncMyCourses(): void
    {
        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
            SyncMyCoursesCompletedEvent::class,
        ]);

        $userToken = UserToken::factory()->create([
            'token' => 'testing',
        ]);

        $service = app(UdemyService::class);
        $coursesDto = $service->syncMyCourses($userToken);

        $this->assertEquals(1, $coursesDto->pages());

        Event::assertDispatchedTimes(
            CourseCreatedEvent::class,
            $coursesDto->getCount()
        );
        Event::assertDispatchedTimes(
            SyncMyCourseCompletedEvent::class,
            $coursesDto->getCount()
        );

        $this->assertDatabaseCount('udemy_courses', $coursesDto->getCount());

        Event::assertNotDispatched(SyncMyCoursesCompletedEvent::class);
    }
}
