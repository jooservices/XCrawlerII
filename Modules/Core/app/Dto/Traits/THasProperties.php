<?php

namespace Modules\Core\Dto\Traits;

use JsonException;
use stdClass;

trait THasProperties
{
    public function toArray(): array
    {
        $class = $this->_class ?? null;

        return array_merge(
            (array) $this->data,
            ['class' => $class]
        );
    }

    /**
     * @throws JsonException
     */
    final public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_THROW_ON_ERROR);
    }

    public function __get(string $name): mixed
    {
        return $this->data->{$name} ?? null;
    }

    public function __set(string $name, mixed $value): void
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
