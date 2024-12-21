<?php

namespace Modules\Core\Dto;

use Illuminate\Support\Str;
use Modules\Client\Interfaces\IResponse;
use Modules\Core\Dto\Interfaces\IDto;
use Modules\Core\Dto\Traits\TCastsDto;
use Modules\Core\Exceptions\InvalidDtoDataException;
use stdClass;

class BaseDto implements IDto
{
    use TCastsDto;

    protected ?stdClass $data;

    protected array $fields = [];

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
                $this->data = json_decode(json_encode($response));
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

    public function __get(string $name)
    {
        if (
            !empty($this->fields)
            && !in_array($name, $this->getFields(), true)
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

    public function __call(string $name, array $arguments)
    {
        $name = str_replace('get', '', $name);
        $name = Str::snake($name);

        // Cast to the correct type
        if (isset($this->fields[$name])) {
            $castTo = $this->fields[$name];

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

    public function toArray(): array
    {
        $class = $this->_class ?? null;

        return array_merge(
            (array) $this->data,
            ['class' => $class]
        );
    }

    public function getFields(): array
    {
        return array_keys($this->fields);
    }
}
