<?php

namespace Modules\Udemy\Interfaces;

use Illuminate\Support\Collection;

interface IResultsListEntity
{
    public function getCount(): int;

    public function getNext(): string;

    public function getPrevious(): string;

    public function pages(): int;

    public function getResults(): Collection;
}
