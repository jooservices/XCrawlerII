<?php

namespace Modules\Core\Dto\Traits;

use stdClass;

trait THasProperties
{
    final public function toArray(): array
    {
        $class = $this->_class ?? null;

        return array_merge(
            (array) $this->data,
            ['class' => $class]
        );
    }

    final public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    public function __get(string $name)
    {
        if (
            !empty($this->casts)
            && !in_array($name, $this->getCasts(), true)
        ) {
            return null;
        }

        return $this->data->{$name} ?? null;
    }

    public function __set($name, $value)
    {
        if (!isset($this->data)) {
            $this->data = new stdClass();
        }

        $this->data->{$name} = $value;
    }

    public function __isset(string $name): bool
    {
        return isset($this->data->{$name});
    }

    public function __unset(string $name): void
    {
        unset($this->data->{$name});
    }
}
