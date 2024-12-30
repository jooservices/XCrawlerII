<?php

namespace Modules\Udemy\Client\Dto\Traits;

/**
 * @property string $_class
 */
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
