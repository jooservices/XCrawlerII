<?php

namespace Modules\Udemy\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Bus\Batch;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SyncMyCoursesProgressingEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public Batch $batch,
    ) {
        //
    }
}
