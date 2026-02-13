<?php

namespace Modules\JAV\Services\Clients;

use JOOservices\Client\Client\ClientBuilder;
use JOOservices\Client\Contracts\HttpClientInterface as Client;

class OneFourOneJavClient
{
    public function __construct(
        private Client $client
    ) {
    }

    public function getFactory(): Client
    {
        return $this->client;
    }

    public function __call(string $method, array $parameters)
    {
        return $this->client->$method(...$parameters);
    }
}
