<?php

namespace Modules\Udemy\Tests\Unit\Events;

use Illuminate\Support\Facades\Notification;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Notifications\CourseReadyForStudyNotification;
use Modules\Udemy\Tests\TestCase;

class CourseReadyForStudyTest extends TestCase
{
    public function testCourseReadyForStudy()
    {
        Notification::fake();

        $userToken = UserToken::factory()->create();
        /**
         * Because there is no items it'll process nothing
         */
        CourseReadyForStudyEvent::dispatch(
            $userToken,
            $userToken->courses->first()
        );

        Notification::assertSentTo(
            $userToken,
            CourseReadyForStudyNotification::class
        );
    }
}
