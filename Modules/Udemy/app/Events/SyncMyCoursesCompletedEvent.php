<?php

namespace Modules\Udemy\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Bus\Batch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Udemy\Client\Dto\CoursesDto;
use Modules\Udemy\Models\UserToken;

class SyncMyCoursesCompletedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public UserToken $userToken
    ) {
        //
    }
}
