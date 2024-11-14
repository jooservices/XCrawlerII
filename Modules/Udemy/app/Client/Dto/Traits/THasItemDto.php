<?php

namespace Modules\Udemy\Client\Dto\Traits;

/**
 * @property string $_class
 */
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
