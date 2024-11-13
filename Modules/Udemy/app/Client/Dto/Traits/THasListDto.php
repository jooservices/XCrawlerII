<?php

namespace Modules\Udemy\Client\Dto\Traits;

use Illuminate\Support\Collection;
use Modules\Core\Dto\Interfaces\IDto;
use Modules\Udemy\Client\Dto\AssessmentDto;

trait THasListDto
{
    public const array FIELDS = [
        'count',
        'next',
        'previous',
        'results',
    ];

    public function getFields(): array
    {
        return self::FIELDS;
    }

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

    /**
     * This one maybe not incorrect
     * @return int
     */
    public function pages(): int
    {
        return (int) ceil($this->data->count / count($this->data->results));
    }

    public function getResults(): Collection
    {
        return collect($this->data->results)->map(function ($item) {
            return app($this->getSingular())->transform($item);
        });
    }

    abstract protected function getSingular(): string;
}
