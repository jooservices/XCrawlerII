<?php

namespace Modules\Core\Dto;

use Modules\Core\Dto\Interfaces\IDto;
use Modules\Core\Dto\Traits\TCastsDto;
use Modules\Core\Dto\Traits\THasProperties;
use stdClass;

class BaseDto implements IDto
{
    use TCastsDto;
    use THasProperties;

    protected ?stdClass $data;
}
