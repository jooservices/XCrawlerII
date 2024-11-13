<?php

namespace Modules\Udemy\Client\Dto\Interfaces;

use Illuminate\Support\Collection;

interface IHasListDto
{
    public function getCount(): int;

    public function getNext(): string;

    public function getPrevious(): string;

    public function pages(): int;

    public function getResults(): Collection;
}
