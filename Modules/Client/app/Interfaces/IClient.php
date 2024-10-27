<?php

namespace Modules\Client\Interfaces;

interface IClient
{
    public function get(string $endpoint, array $payload = [], array $options = []): IResponse;

    public function post(string $endpoint, array $payload = [], array $options = []): IResponse;

    public function put(string $endpoint, array $payload = [], array $options = []): IResponse;

    public function delete(string $endpoint, array $payload = [], array $options = []): IResponse;

    public function patch(string $endpoint, array $payload = [], array $options = []): IResponse;

    public function request(string $method, string $endpoint, array $payload = [], array $options = []): IResponse;
}
