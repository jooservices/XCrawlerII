<?php

namespace Modules\Udemy\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\Entities\CoursesEntity;

class SyncMyCoursesCompletedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public UserToken $userToken,
        public CoursesEntity $coursesEntity
    ) {
        //
    }
}