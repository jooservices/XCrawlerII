<?php

namespace Modules\Jav\Events\Onejav;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Jav\Dto\ItemDto;

class ItemParsedEvent
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(public ItemDto $item)
    {
        //
    }
}
