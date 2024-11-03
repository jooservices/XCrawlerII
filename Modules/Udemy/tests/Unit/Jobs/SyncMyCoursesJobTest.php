<?php

namespace Modules\Udemy\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
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
        ]);
        /**
         * @var UserToken $userToken
         */
        $userToken = UserToken::factory()->create();

        SyncMyCoursesJob::dispatch($userToken->token);

        Event::assertDispatched(UdemyCourseCreatedEvent::class);
        $this->assertDatabaseCount('udemy_courses', 88);
        $this->assertCount(88, $userToken->courses);
    }
}
