<?php

namespace Modules\Jav\Dto;

use Modules\Core\Dto\AbstractBaseDto;

/**
 * @property string $name
 * @property string $url
 */
class TagDto extends AbstractBaseDto
{
    public function getFields(): array
    {
        return [];
    }
}
