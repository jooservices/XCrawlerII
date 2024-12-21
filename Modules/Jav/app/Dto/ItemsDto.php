<?php

namespace Modules\Jav\Dto;

use Illuminate\Support\Collection;
use Modules\Core\Dto\BaseDto;
use Modules\Core\Dto\Traits\TDefaultDto;
use stdClass;

/**
 * @property int $page
 * @property int $last_page
 * @property Collection|ItemDto $items
 */
class ItemsDto extends BaseDto
{
    use TDefaultDto;

    public function transform(mixed $response): ?static
    {
        $this->data = new stdClass();
        $this->data->items = $response['items'];
        $this->data->page = $response['page'];
        $this->data->last_page = $response['last_page'];

        return $this;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    public function getPage(): int
    {
        return $this->page;
    }

    public function getLastPage(): int
    {
        return $this->last_page ?? 1;
    }

    public function isLastPage(): bool
    {
        return $this->page >= $this->last_page;
    }

    public function count(): int
    {
        return $this->items->count();
    }
}
