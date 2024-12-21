<?php

namespace Modules\Core\Dto;

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
                    throw new InvalidDtoDataException($response->getMessage());
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
        return $this->data->{$name} ?? null;
    }

    public function __set($name, $value)
    {
        if (!isset($this->data)) {
            $this->data = new stdClass();
        }

        $this->data->{$name} = $value;
    }

    public function __isset($name)
    {
        return isset($this->data->{$name});
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
        return $this->fields;
    }
}
