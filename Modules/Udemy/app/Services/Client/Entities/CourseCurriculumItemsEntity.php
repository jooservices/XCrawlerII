<?php

namespace Modules\Udemy\Services\Client\Entities;

use Illuminate\Support\Collection;
use Modules\Udemy\Events\ListEntityHaveNoResultsEvent;
use Modules\Udemy\Interfaces\IResultsListEntity;
use Modules\Udemy\Services\Client\Entities\Traits\TResultsEntityList;

class CourseCurriculumItemsEntity extends AbstractBaseEntity implements IResultsListEntity
{
    use TResultsEntityList;

    public function getResults(): Collection
    {
        if (!isset($this->data->results)) {
            ListEntityHaveNoResultsEvent::dispatch($this);

            return collect();
        }

        return collect($this->data->results)->map(function ($item) {
            return new CurriculumItemEntity($item);
        });
    }
}
