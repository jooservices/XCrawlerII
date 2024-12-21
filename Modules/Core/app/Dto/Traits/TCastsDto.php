<?php

namespace Modules\Core\Dto\Traits;

trait TCastsDto
{
    public function getInt(string $name): int
    {
        return (int) $this->data->{$name};
    }

    public function getFloat(string $name): float
    {
        return (float) $this->data->{$name};
    }

    public function getBool(string $name): bool
    {
        return (bool) $this->data->{$name};
    }

    public function getArray(string $name): array
    {
        return (array) $this->data->{$name};
    }

    public function getObject(string $name): object
    {
        return (object) $this->data->{$name};
    }

    public function getString(string $name): string
    {
        return (string) $this->data->{$name};
    }

    public function getNull(string $name): ?string
    {
        return $this->data->{$name} ?? null;
    }
}
