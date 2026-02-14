<?php

namespace Modules\JAV\Dtos;

use Illuminate\Support\Collection;
use JOOservices\Dto\Core\Dto;

class XcityIdolPage extends Dto
{
    /**
     * @param  Collection<int, XcityIdol>  $idols
     */
    public function __construct(
        public readonly Collection $idols,
        public readonly ?string $nextUrl = null,
    ) {}
}
