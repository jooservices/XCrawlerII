<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\AbstractBaseDto;
use Modules\Udemy\Client\Dto\Interfaces\IHasListDto;
use Modules\Udemy\Client\Dto\Traits\THasListDto;

class AssessmentsDto extends AbstractBaseDto implements IHasListDto
{
    use THasListDto;

    public function getFields(): array
    {
        return [];
    }

    protected function getSingular(): string
    {
        return AssessmentDto::class;
    }
}
