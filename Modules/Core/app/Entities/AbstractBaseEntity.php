<?php

namespace Modules\Core\Entities;

use stdClass;

abstract class AbstractBaseEntity
{
    public function __construct(protected stdClass $data)
    {
    }

    public function __get(string $name)
    {
        return $this->data->{$name} ?? null;
    }

    public function toArray(): array
    {
        return (array) $this->data;
    }
}
