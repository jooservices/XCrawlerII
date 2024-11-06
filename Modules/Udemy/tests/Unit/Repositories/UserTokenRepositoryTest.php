<?php

namespace Modules\Udemy\Tests\Unit\Repositories;

use Illuminate\Support\Facades\Event;
use Modules\Udemy\Events\Courses\CourseCreatedEvent;
use Modules\Udemy\Events\Courses\UserCourseSyncCompletedEvent;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Repositories\UserTokenRepository;
use Modules\Udemy\Tests\TestCase;

class UserTokenRepositoryTest extends TestCase
{
    public function testSyncCourseSuccess()
    {
        Event::fake([
            CourseCreatedEvent::class,
            UserCourseSyncCompletedEvent::class,
        ]);

        $userToken = UserToken::factory()->create();
        $udemyCourse = UdemyCourse::factory()->create();

        app(UserTokenRepository::class)->syncCourse($userToken, $udemyCourse);

        Event::assertDispatched(CourseCreatedEvent::class);
        Event::assertDispatched(UserCourseSyncCompletedEvent::class);
    }

    public function testSyncCourseAlreadyCreatedBefore()
    {
        Event::fake([
            CourseCreatedEvent::class,
            UserCourseSyncCompletedEvent::class,
        ]);

        $userToken = UserToken::factory()->create();
        $udemyCourse = UdemyCourse::factory()->create();
        $udemyCourse->update([
            'title' => $this->faker->sentence,
        ]);

        app(UserTokenRepository::class)->syncCourse($userToken, $udemyCourse);

        Event::assertNotDispatched(CourseCreatedEvent::class);
        Event::assertDispatched(UserCourseSyncCompletedEvent::class);
    }
}
