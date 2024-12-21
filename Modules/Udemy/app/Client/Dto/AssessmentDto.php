<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\BaseDto;

class AssessmentDto extends BaseDto
{
    protected array $fields = [
        'id',
        'correct_response',
    ];

    public function getId(): int
    {
        return $this->data->id;
    }

    public function getCorrectResponse(): array
    {
        return $this->data->correct_response;
    }
}
