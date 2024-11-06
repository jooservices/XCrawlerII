<?php

namespace Modules\Udemy\Tests\Unit\Events;

use Illuminate\Support\Facades\Notification;
use Modules\Udemy\Events\SyncMyCoursesCompletedEvent;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\CoursesSyncCompletedNotification;
use Modules\Udemy\Services\Client\Entities\CoursesEntity;
use Modules\Udemy\Tests\TestCase;

class SyncMyCoursesCompletedEventTest extends TestCase
{
    public function testSyncMyCoursesCompletedEvent()
    {
        Notification::fake();
        $userToken = UserToken::factory()
            ->withCourse()
            ->create();

        SyncMyCoursesCompletedEvent::dispatch($userToken, new CoursesEntity([]));
        Notification::assertSentTo(
            $userToken,
            CoursesSyncCompletedNotification::class,
            function (CoursesSyncCompletedNotification $notification) use ($userToken) {
                return $notification->userToken->is($userToken);
            }
        );
    }
}
