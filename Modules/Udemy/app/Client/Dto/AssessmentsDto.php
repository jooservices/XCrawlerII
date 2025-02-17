<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\BaseDto;
use Modules\Udemy\Client\Dto\Interfaces\IHasListDto;
use Modules\Udemy\Client\Dto\Traits\THasListDto;

class AssessmentsDto extends BaseDto implements IHasListDto
{
    use THasListDto;

    protected function getSingular(): string
    {
        return AssessmentDto::class;
    }
}
