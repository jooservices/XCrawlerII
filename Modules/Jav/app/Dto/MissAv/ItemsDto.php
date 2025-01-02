<?php

namespace Modules\Jav\Dto\MissAv;

use Illuminate\Support\Collection;
use Modules\Jav\Dto\BaseDto;
use Modules\Jav\Dto\ItemDto;
use stdClass;

/**
 * @property int $page
 * @property int $last_page
 * @property Collection|ItemDto $items
 */
class ItemsDto extends BaseDto
{
    final public function transform(mixed $response): ?static
    {
        $this->data = new stdClass();
        $this->data->items = $response['items'];
        $this->data->page = $response['page'];
        $this->data->last_page = $response['last_page'];

        return $this;
    }

    final public function getItems(): Collection
    {
        return $this->items;
    }

    final public function getPage(): int
    {
        return $this->page;
    }

    final public function getLastPage(): int
    {
        return $this->last_page ?? 1;
    }

    final public function isLastPage(): bool
    {
        return $this->page >= $this->last_page;
    }

    final public function count(): int
    {
        return $this->items->count();
    }
}
