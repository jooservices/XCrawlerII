<?php

namespace Modules\Udemy\Client\Dto\Traits;

trait THasItemDto
{
    public function getId(): int
    {
        return $this->id;
    }

    public function getClass(): string
    {
        return $this->_class;
    }
}
