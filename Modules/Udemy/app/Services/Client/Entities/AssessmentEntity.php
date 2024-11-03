<?php

namespace Modules\Udemy\Services\Client\Entities;

use Illuminate\Support\Collection;
use Modules\Udemy\Interfaces\IResultsListEntity;
use Modules\Udemy\Services\Client\Entities\Traits\TResultsEntityList;

class AssessmentEntity extends AbstractBaseEntity
{
    public function getCorrectResponse(): array
    {
        return $this->data->correct_response;
    }
}
