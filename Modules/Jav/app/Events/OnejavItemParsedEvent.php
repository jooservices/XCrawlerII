<?php

namespace Modules\Jav\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Jav\Entities\OnejavItemEntity;

class OnejavItemParsedEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public OnejavItemEntity $item)
    {
        //
    }
}
