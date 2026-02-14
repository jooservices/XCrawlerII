<?php

namespace Modules\JAV\Dtos;

use JOOservices\Dto\Core\Dto;

class XcityIdol extends Dto
{
    public function __construct(
        public readonly string $xcityId,
        public readonly string $name,
        public readonly string $detailUrl,
        public readonly ?string $coverImage = null,
    ) {}
}
