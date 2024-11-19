<?php

namespace Modules\Jav\Events\Onejav;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Jav\Models\Interfaces\IJavMovie;

class ReferenceCreatedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public IJavMovie $movie)
    {
        //
    }
}
