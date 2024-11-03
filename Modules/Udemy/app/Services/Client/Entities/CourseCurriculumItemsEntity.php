<?php

namespace Modules\Udemy\Services\Client\Entities;

use Illuminate\Support\Collection;
use Modules\Udemy\Interfaces\IResultsListEntity;
use Modules\Udemy\Services\Client\Entities\Traits\TResultsEntityList;

class CourseCurriculumItemsEntity extends AbstractBaseEntity implements IResultsListEntity
{
    use TResultsEntityList;

    public function getResults(): Collection
    {
        return collect($this->data->results)->map(function ($item) {
            return new CurriculumItemEntity($item);
        });
    }
}
