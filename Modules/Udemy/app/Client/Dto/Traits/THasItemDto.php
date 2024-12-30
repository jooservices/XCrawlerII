<?php

namespace Modules\Udemy\Client\Dto\Traits;

trait THasItemDto
{
    final public function getId(): int
    {
        return $this->id;
    }

    final public function getClass(): string
    {
        return $this->_class;
    }
}
