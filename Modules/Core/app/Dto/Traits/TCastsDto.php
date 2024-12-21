<?php

namespace Modules\Core\Dto\Traits;

use Illuminate\Support\Str;

trait TCastsDto
{
    protected array $casts = [];

    final public function getCasts(): array
    {
        return array_keys($this->casts);
    }

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

    public function __call(string $name, array $arguments): mixed
    {
        $name = str_replace('get', '', $name);
        $name = Str::snake($name);

        if (!isset($this->casts[$name])) {
            return $this->data->{$name} ?? null;
        }

        $castTo = $this->casts[$name];

        switch ($castTo) {
            case 'int':
            case 'integer':
                return $this->getInt($name);
            case 'float':
                return $this->getFloat($name);
            case 'bool':
            case 'boolean':
                return $this->getBool($name);
            case 'array':
                return $this->getArray($name);
            case 'object':
                return $this->getObject($name);
            case 'string':
                return $this->getString($name);
        }

        return null;
    }
}
