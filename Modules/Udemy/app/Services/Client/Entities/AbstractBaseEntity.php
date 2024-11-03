<?php

namespace Modules\Udemy\Services\Client\Entities;

abstract class AbstractBaseEntity
{
    public function __construct(protected mixed $data)
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

    public function getId(): int
    {
        return $this->data->id;
    }

    public function getClass(): string
    {
        return $this->data->_class;
    }

    public function getTitle(): string
    {
        return $this->data->title;
    }
}
