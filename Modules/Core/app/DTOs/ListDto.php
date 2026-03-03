<?php

declare(strict_types=1);

namespace Modules\Core\DTOs;

use Illuminate\Support\Collection;

final readonly class ListDto
{
    public function __construct(
        public Collection $items,
        public PaginationDto $pagination,
    ) {
    }
}
