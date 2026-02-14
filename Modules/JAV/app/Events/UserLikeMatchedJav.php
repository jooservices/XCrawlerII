<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\JAV\Models\UserLikeNotification;

class UserLikeMatchedJav
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly UserLikeNotification $notification
    ) {}
}
