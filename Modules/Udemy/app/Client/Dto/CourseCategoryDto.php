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

    public const array FIELDS = [
        'id',
        'title',
    ];

    public function getFields(): array
    {
        return self::FIELDS;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
