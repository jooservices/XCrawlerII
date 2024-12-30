<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\BaseDto;
use Modules\Udemy\Client\Dto\Interfaces\IHasItemDto;
use Modules\Udemy\Client\Dto\Traits\THasItemDto;

/**
 * @property int $id
 * @property string $title
 */
class CourseCategoryDto extends BaseDto implements IHasItemDto
{
    use THasItemDto;

    public const string DTO_NAME = 'course_category';

    protected array $casts = [
        'id' => 'int',
        'title' => 'string',
        'class' => 'string',
    ];

    public const array FIELDS = [
        'id',
        'title',
    ];

    final public function getTitle(): string
    {
        return $this->title;
    }
}
