<?php

namespace Modules\JAV\Contracts;

use Modules\JAV\Dtos\Items;

interface IItems
{
    public function hasNextPage(): bool;
    public function nextPage(): int;
    public function currentPage(): int;
    public function items(): Items;
}
