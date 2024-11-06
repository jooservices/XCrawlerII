<?php

namespace Modules\Udemy\Tests\Unit\Events;

use Illuminate\Support\Facades\Queue;
use Modules\Udemy\Events\Courses\UserCourseSyncCompletedEvent;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;

class UserCourseSyncCompletedEventTest extends TestCase
{
    public function testEventDispatched(): void
    {
        Queue::fake(SyncCurriculumItemsJob::class);

        $userToken = UserToken::factory()
            ->withCourse()
            ->create();

        $course = $userToken->courses->first();

        UserCourseSyncCompletedEvent::dispatch($userToken, $course);

        Queue::assertPushed(SyncCurriculumItemsJob::class);
    }
}
