<?php

namespace Modules\Client\Services\Clients\ResponseData;

use Modules\Client\Exceptions\PropertyNotFound;
use Modules\Client\Interfaces\IResponseData;

class BaseResponseData implements IResponseData
{
    private mixed $data;

    public function __construct(private readonly string $body)
    {
        $this->data = $this->parseResponse();
    }

    protected function parseResponse(): mixed
    {
        return $this->body;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function __get(string $name)
    {
        if (
            is_object($this->data)
            && isset($this->data->{$name})
        ) {
            throw new PropertyNotFound("Property '{$name}' not found");
        }

        return $this->data->{$name};
    }
}
