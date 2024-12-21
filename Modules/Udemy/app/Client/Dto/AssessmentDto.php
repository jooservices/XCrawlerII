<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\BaseDto;

class AssessmentDto extends BaseDto
{
    protected array $fields = [
        'id' => 'int',
        'correct_response' => 'array',
    ];
}
