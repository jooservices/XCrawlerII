<?php

namespace Modules\Udemy\Client\Dto;

use Illuminate\Support\Collection;
use Modules\Core\Dto\BaseDto;
use Modules\Udemy\Client\Dto\Interfaces\IHasListDto;
use Modules\Udemy\Client\Dto\Traits\THasListDto;

class CourseCurriculumItemsDto extends BaseDto implements IHasListDto
{
    use THasListDto;

    public const string DTO_NAME = 'course';

    final public function getResults(): Collection
    {
        return collect($this->data->results)->map(function ($item) {
            return (new CourseCurriculumItemDto())->transform($item);
        });
    }

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

    final protected function getSingular(): string
    {
        return CourseCurriculumItemDto::class;
    }
}
