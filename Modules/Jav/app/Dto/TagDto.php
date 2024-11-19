<?php

namespace Modules\Jav\Dto;

use Modules\Core\Dto\AbstractBaseDto;
use Modules\Core\Dto\Traits\TDefaultDto;

/**
 * @property string $name
 * @property string $url
 * @property string $slug
 * @property string $link
 */
class TagDto extends AbstractBaseDto
{
    use TDefaultDto;
}
