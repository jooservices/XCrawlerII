<?php

namespace Modules\Core\Dto;

use Modules\Client\Interfaces\IResponse;
use Modules\Core\Dto\Interfaces\IDto;
use stdClass;

abstract class AbstractBaseDto implements IDto
{
    protected stdClass $data;

    public function transform(mixed $response): ?static
    {
        if ($response instanceof IResponse) {
            if (!$response->isSuccess()) {
                return null;
            }

            $this->data = $response->parseBody()->getData();
        } else {
            $this->data = $response;
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

    public function toArray(): array
    {
        return array_merge(
            (array) $this->data,
            ['class' => $this->_class]
        );
    }

    abstract public function getFields(): array;
}
