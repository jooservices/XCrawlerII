<?php

namespace Modules\Udemy\Repositories;

use Carbon\Carbon;
use Modules\Udemy\Events\UdemyCourseCreatedEvent;
use Modules\Udemy\Events\UserCourseSyncCompleted;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;

class UserTokenRepository
{
    public function createWithToken(string $token): UserToken
    {
        return UserToken::updateOrCreate([
            'token' => $token,
        ]);
    }

    public function syncCourse(
        UserToken $userToken,
        UdemyCourse $udemyCourse,
        int $completionRatio = 0,
        ?Carbon $enrollmentTime = null
    ) {
        $enrollmentTime = $enrollmentTime ?? Carbon::now();

        $userToken->courses()->syncWithoutDetaching([
            $udemyCourse->id => [
                'completion_ratio' => $completionRatio,
                'enrollment_time' => $enrollmentTime,
            ],
        ]);

        if ($udemyCourse->wasRecentlyCreated) {
            UdemyCourseCreatedEvent::dispatch($userToken, $udemyCourse);
        }

        UserCourseSyncCompleted::dispatch($userToken, $udemyCourse);
    }
}
