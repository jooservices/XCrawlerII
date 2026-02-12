<?php

namespace Modules\JAV\Contracts;

use Modules\JAV\Dtos\Item;

interface IItemParsed
{
    /**
     * Get the parsed item.
     */
    public function getItem(): Item;

    /**
     * Get the source identifier.
     */
    public function getSource(): string;
}
