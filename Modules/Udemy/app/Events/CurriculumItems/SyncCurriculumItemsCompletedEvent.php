<?php

namespace Modules\Udemy\Events\CurriculumItems;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\Entities\CourseCurriculumItemsEntity;

class SyncCurriculumItemsCompletedEvent
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
        public CourseCurriculumItemsEntity $curriculumItems,
    ) {
        //
    }
}
