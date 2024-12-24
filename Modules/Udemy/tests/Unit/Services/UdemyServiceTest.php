<?php

namespace Modules\Udemy\Tests\Unit\Services;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Testing\Fakes\PendingBatchFake;
use Modules\Udemy\Events\CourseCreatedEvent;
use Modules\Udemy\Events\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\UdemyService;
use Modules\Udemy\Tests\TestCase;
use Throwable;

class UdemyServiceTest extends TestCase
{
    private UdemyService $service;

    public function setUp(): void
    {
        parent::setUp();

        $this->service = app(UdemyService::class);
    }

    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    final public function testSyncMyCourse(): void
    {
        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
        ]);

        $userToken = UserToken::factory()->create([
            'token' => 'testing',
        ]);

        $coursesDto = $this->service->syncMyCourses($userToken);

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

    /**
     * @throws Throwable
     * @throws BindingResolutionException
     */
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

        $coursesDto = $this->service->syncMyCourses($userToken);

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

    /**
     * @throws BindingResolutionException
     * @throws Throwable
     */
    final public function testSyncMyCoursesMultiPages(): void
    {
        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
            SyncMyCoursesCompletedEvent::class,
        ]);

        Bus::fake();

        $userToken = UserToken::factory()->create([
            'token' => 'testing',
        ]);

        $coursesDto = $this->service->syncMyCourses($userToken, [
            'page_size' => 40,
        ]);

        $this->assertEquals(3, $coursesDto->pages());

        Bus::assertBatched(static function (PendingBatchFake $callback) {
            return $callback->jobs->count() === 2;
        });
    }
}
