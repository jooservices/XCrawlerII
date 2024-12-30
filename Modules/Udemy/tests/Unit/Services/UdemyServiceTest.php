<?php

namespace Modules\Udemy\Tests\Unit\Services;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Testing\Fakes\PendingBatchFake;
use Modules\Udemy\Events\CourseCreatedEvent;
use Modules\Udemy\Events\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Services\UdemyService;
use Modules\Udemy\Tests\TestCase;
use Modules\Udemy\Zeus\Wishes\UdemyWish;
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
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCourses()
            ->wish();

        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
        ]);

        $coursesDto = $this->service->syncMyCourse($this->userToken);

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

    final public function testSyncMyCoursePaging(): void
    {
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCoursesPaging()
            ->wish();

        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
        ]);

        $coursesDto = $this->service->syncMyCourse($this->userToken);

        $this->assertEquals(3, $coursesDto->pages());
    }

    /**
     * @throws Throwable
     * @throws BindingResolutionException
     */
    final public function testSyncMyCourses(): void
    {
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCourses()
            ->wish();

        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
            SyncMyCoursesCompletedEvent::class,
        ]);

        $coursesDto = $this->service->syncMyCourses($this->userToken);

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
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCoursesPaging()
            ->wish();

        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
            SyncMyCoursesCompletedEvent::class,
        ]);

        Bus::fake();

        $coursesDto = $this->service->syncMyCourses($this->userToken);

        $this->assertEquals(3, $coursesDto->pages());

        Bus::assertBatched(static function (PendingBatchFake $callback) {
            return $callback->jobs->count() === 2;
        });
    }

    /**
     * @throws BindingResolutionException
     */
    final public function testSyncCurriculumItem(): void
    {
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscriberCurriculumItems()
            ->wish();

        $course = UdemyCourse::factory()->create(['id' => UdemyWish::COURSE_ID]);

        $this->userToken->courses()->attach($course, [
            'completion_ratio' => 100,
            'enrollment_time' => now(),
        ]);

        $this->service->syncCurriculumItem(
            $this->userToken,
            $course
        );

        $this->assertDatabaseCount('curriculum_items', 54);
    }

    final public function testSyncCurriculumItems(): void
    {
        $this->wish
            ->setToken($this->userToken)
            ->wishSubscriberCurriculumItems()
            ->wish();

        $course = UdemyCourse::factory()->create(['id' => UdemyWish::COURSE_ID]);

        $this->userToken->courses()->attach($course, [
            'completion_ratio' => 100,
            'enrollment_time' => now(),
        ]);

        $this->service->syncCurriculumItems(
            $this->userToken,
            $course
        );

        $this->assertDatabaseCount('curriculum_items', 54);
    }
}
