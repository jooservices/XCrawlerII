<?php

namespace Modules\Udemy\Client\Dto;

use Illuminate\Support\Collection;
use Modules\Core\Dto\BaseDto;
use Modules\Udemy\Client\Dto\Interfaces\IHasListDto;
use Modules\Udemy\Client\Dto\Traits\THasListDto;

class CourseCategoriesDto extends BaseDto implements IHasListDto
{
    use THasListDto;

    public const int PAGE_SIZE = 15;

    public static function getPayload(): array
    {
        return [
            'fields' => [
                CourseCategoryDto::DTO_NAME => implode(',', CourseCategoryDto::FIELDS),
            ],
            'previewing' => false,
            'page_size' => CourseCategoriesDto::PAGE_SIZE,
            'is_archived' => false,
        ];
    }

    public function getResults(): Collection
    {
        return collect($this->data->results)->map(function ($item) {
            return (new CourseCategoryDto())->transform($item);
        });
    }

    protected function getSingular(): string
    {
        return CourseCategoryDto::class;
    }
}
