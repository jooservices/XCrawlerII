<?php

namespace Modules\Udemy\Tests\Unit\Events;

use Illuminate\Support\Facades\Queue;
use Modules\Udemy\Events\UdemyCourseCreatedEvent;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;

class UdemyCourseCreatedEventTest extends TestCase
{
    public function testEventDispatched(): void
    {
        Queue::fake(
            SyncCurriculumItemsJob::class
        );
        $userToken = UserToken::factory()
            ->withCourse()
            ->create();

        $course = $userToken->courses->first();

        UdemyCourseCreatedEvent::dispatch($userToken, $course);

        Queue::assertPushed(SyncCurriculumItemsJob::class);
    }
}
