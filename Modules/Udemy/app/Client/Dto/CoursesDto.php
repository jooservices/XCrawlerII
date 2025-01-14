<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\BaseDto;
use Modules\Udemy\Client\Dto\Interfaces\IHasListDto;
use Modules\Udemy\Client\Dto\Traits\THasListDto;

class CoursesDto extends BaseDto implements IHasListDto
{
    use THasListDto;

    public const string DTO_NAME = 'course';

    public static function getPayload(): array
    {
        return [
            'fields' => [
                'course' => implode(',', CourseDto::FIELDS),
                'users' => '@min,job_title',
            ],
            'ordering' => '-last_accessed',
            'page' => 1,
            'page_size' => 100,
            'is_archived' => false,
        ];
    }

    protected function getSingular(): string
    {
        return CourseDto::class;
    }
}
