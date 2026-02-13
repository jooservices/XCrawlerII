<?php

namespace Modules\JAV\Dtos;

use Illuminate\Support\Collection;
use JOOservices\Dto\Core\Dto;

class Item extends Dto
{
    public function __construct(
        public readonly ?string $id,
        public readonly ?string $title,
        public readonly ?string $url,
        public readonly ?string $image,
        public readonly ?\Carbon\Carbon $date,
        public readonly ?string $code,
        public readonly Collection $tags,
        public readonly ?float $size = null,
        public readonly ?string $description = null,
        public readonly Collection $actresses = new Collection,
        public readonly ?string $download = null,
    ) {}
}
