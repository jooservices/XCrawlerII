<?php

namespace Modules\Jav\Dto;

use Modules\Core\Dto\AbstractBaseDto;
use Modules\Core\Dto\Traits\TDefaultDto;

/**
 * @property string $name
 * @property string $url
 */
class TagDto extends AbstractBaseDto
{
    use TDefaultDto;
}
