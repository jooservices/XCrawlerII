<?php

namespace Modules\Client\Services\Clients\Traits;

use Modules\Client\Interfaces\IResponse;

trait THasRequests
{
    public function get(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    public function post(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    public function put(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    public function delete(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    public function patch(
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        return $this->request(__FUNCTION__, $endpoint, $payload, $options);
    }

    abstract public function request(
        string $method,
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse;
}
