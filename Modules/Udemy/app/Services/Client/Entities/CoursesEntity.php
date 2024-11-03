<?php

namespace Modules\Udemy\Services\Client\Entities;

use Illuminate\Support\Collection;
use Modules\Udemy\Interfaces\IResultsListEntity;
use Modules\Udemy\Services\Client\Entities\Traits\TResultsEntityList;

class CoursesEntity extends AbstractBaseEntity implements IResultsListEntity
{
    use TResultsEntityList;

    public function getResults(): Collection
    {
        if (!isset($this->data->results)) {
            return collect();
        };

        return collect($this->data->results)->map(function ($item) {
            return new CourseEntity($item);
        });
    }
}
