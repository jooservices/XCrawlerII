<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\AbstractBaseDto;

class AssessmentDto extends AbstractBaseDto
{
    public function getId(): int
    {
        return $this->data->id;
    }

    public function getCorrectResponse(): array
    {
        return $this->data->correct_response;
    }

    public function getFields(): array
    {
        return [];
    }
}
