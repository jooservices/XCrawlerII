<?php

namespace Modules\Udemy\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;

class CourseCurriculumItemAttachedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public UserToken $userToken,
        public UdemyCourse $udemyCourse,
        public CurriculumItem $curriculumItem,
        public int $total
    ) {
        //
    }
}