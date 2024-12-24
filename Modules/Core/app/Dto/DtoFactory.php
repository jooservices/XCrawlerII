<?php

namespace Modules\Core\Dto;

use Modules\Core\Dto\Interfaces\IDto;

class DtoFactory
{
    public static function make(mixed $resource, string $dtoClass): IDto
    {
        return app($dtoClass)->transform($resource);
    }
}
