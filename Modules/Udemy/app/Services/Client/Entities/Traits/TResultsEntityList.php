<?php

namespace Modules\Udemy\Services\Client\Entities\Traits;

use Illuminate\Support\Collection;

trait TResultsEntityList
{
    public function getCount(): int
    {
        return $this->data->count;
    }

    public function getNext(): string
    {
        return $this->data->next;
    }

    public function getPrevious(): string
    {
        return $this->data->previous;
    }

    public function pages(): int
    {
        return (int) ceil($this->data->count / count($this->data->results));
    }
}
