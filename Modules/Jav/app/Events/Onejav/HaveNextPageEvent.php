<?php

namespace Modules\Jav\Events\Onejav;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HaveNextPageEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $endpoint,
        public int $currentPage,
        public int $lastPage,
    ) {
    }
}
