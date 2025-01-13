<?php

namespace Feature\Commands;

use Illuminate\Support\Facades\Event;
use Modules\Core\Exceptions\InvalidDtoDataException;
use Modules\Udemy\Events\CourseCreatedEvent;
use Modules\Udemy\Events\SyncMyCourseCompletedEvent;
use Modules\Udemy\Tests\TestCase;

class SyncCoursesTest extends TestCase
{
    final public function testSyncCourses(): void
    {
        Event::fake([
            CourseCreatedEvent::class,
            SyncMyCourseCompletedEvent::class,
        ]);

        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCourses()
            ->wish();

        $this->artisan('udemy:sync-courses')
            ->expectsQuestion('Enter your Udemy token', $this->userToken->token)
            ->expectsQuestion('Sync curriculum items', 'Yes')
            ->assertExitCode(0);
    }

    final public function testSyncCourseError(): void
    {
        $this->expectException(InvalidDtoDataException::class);

        $this->wish
            ->setToken($this->userToken)
            ->wishSubscribedCourses(100, true)
            ->wish();

        $this->artisan('udemy:sync-courses')
            ->expectsQuestion('Enter your Udemy token', $this->userToken->token)
            ->expectsQuestion('Sync curriculum items', 'Yes')
            ->assertExitCode(1);
    }
}
