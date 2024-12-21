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

        return match ($castTo) {
            'int', 'integer' => $this->getInt($name),
            'float' => $this->getFloat($name),
            'bool', 'boolean' => $this->getBool($name),
            'array' => $this->getArray($name),
            'object' => $this->getObject($name),
            'string' => $this->getString($name),
            default => null,
        };
    }
}
