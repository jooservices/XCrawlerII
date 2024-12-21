<?php

namespace Modules\Core\Dto\Traits;

use Illuminate\Support\Str;
use Modules\Client\Interfaces\IResponse;
use Modules\Core\Exceptions\InvalidDtoDataException;

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

    public function transform(mixed $response): ?static
    {
        if ($response === null) {
            throw new InvalidDtoDataException('Response is null');
        }

        switch (true) {
            case $response instanceof IResponse:
                if (!$response->isSuccess()) {
                    throw new InvalidDtoDataException('Response is not successful');
                }

                $this->data = $response->parseBody()->getData();
                break;
            case is_array($response):
                $this->data = json_decode(json_encode($response), false);
                break;
            case is_object($response):
                $this->data = $response;
                break;
            default:
                return $this->data = $response;
        }

        /**
         * @TODO Validate data
         */

        return $this;
    }

    public function __call(string $name, array $arguments)
    {
        $name = str_replace('get', '', $name);
        $name = Str::snake($name);

        // Cast to the correct type
        if (isset($this->casts[$name])) {
            $castTo = $this->casts[$name];

            switch ($castTo) {
                case 'int':
                    return $this->getInt($name);
                case 'float':
                    return $this->getFloat($name);
                case 'bool':
                    return $this->getBool($name);
                case 'array':
                    return $this->getArray($name);
                case 'object':
                    return $this->getObject($name);
                case 'string':
                    return $this->getString($name);
                case 'null':
                    return $this->getNull($name);
            }
        }

        if (isset($this->data->{$name})) {
            return $this->data->{$name};
        }

        return null;
    }
}
