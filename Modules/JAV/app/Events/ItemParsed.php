<?php

namespace Modules\JAV\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\JAV\Contracts\IItemParsed;
use Modules\JAV\Dtos\Item;

class ItemParsed implements IItemParsed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Item $item,
        public readonly string $source
    ) {}

    public function getItem(): Item
    {
        return $this->item;
    }

    public function getSource(): string
    {
        return $this->source;
    }
}
