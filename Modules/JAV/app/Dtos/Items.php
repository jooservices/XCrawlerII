<?php

namespace Modules\JAV\Dtos;

use Illuminate\Support\Collection;
use JOOservices\Dto\Core\Dto;

class Items extends Dto
{
    /**
     * @param  Collection<int, Item>  $items
     */
    public function __construct(
        public readonly Collection $items,
        public readonly bool $hasNextPage,
        public readonly int $nextPage,
    ) {}
}
