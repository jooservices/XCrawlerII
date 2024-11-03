<?php

namespace Modules\Udemy\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Modules\Udemy\Events\UdemyCourseCreatedEvent;
use Modules\Udemy\Jobs\SyncMyCoursesJob;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;

class SyncMyCoursesJobTest extends TestCase
{
    public function testSuccess()
    {


        $userToken = UserToken::factory()->create([
            'token' => 'YePCcKI8NroxcsAKGsqfX4CVsIwKtLbkTQPWEuKi'
        ]);

        SyncMyCoursesJob::dispatch($userToken->token);

        //Event::assertDispatched(UdemyCourseCreatedEvent::class);
        $this->assertDatabaseCount('udemy_courses', 88);
        $this->assertCount(88, $userToken->courses);
    }
}
