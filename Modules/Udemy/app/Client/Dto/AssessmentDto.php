<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\BaseDto;

/**
 * @property int $id
 * @property array $correct_response
 *
 * @method int getId()
 * @method array getCorrectResponse()
 */
class AssessmentDto extends BaseDto
{
    protected array $casts = [
        'id' => 'int',
        'correct_response' => 'array',
    ];
}
