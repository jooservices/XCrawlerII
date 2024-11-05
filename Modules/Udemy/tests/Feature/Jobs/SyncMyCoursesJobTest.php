<?php

namespace Modules\Udemy\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Events\UdemyCourseCreatedEvent;
use Modules\Udemy\Jobs\SyncMyCoursesJob;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;

class SyncMyCoursesJobTest extends TestCase
{
    public function testSuccess()
    {
        Event::fake([
            UdemyCourseCreatedEvent::class,
            SyncMyCoursesCompletedEvent::class,
        ]);

        /**
         * @var UserToken $userToken
         */
        $userToken = UserToken::factory()
            ->create();

        SyncMyCoursesJob::dispatch($userToken->token);

        Event::assertDispatched(UdemyCourseCreatedEvent::class);
        $this->assertDatabaseCount('udemy_courses', 88);
        $this->assertCount(88, $userToken->refresh()->courses);

        Event::assertDispatched(SyncMyCoursesCompletedEvent::class);

        /**
         * @TODO Test when UdemyCourseCreatedEvent is not dispatched
         */
    }
}
