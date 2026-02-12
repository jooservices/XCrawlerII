<?php

namespace Modules\JAV\Services\Clients;

use JOOservices\Client\Client\ClientBuilder;
use JOOservices\Client\Client\HttpClient as Client;

class OneFourOneJavClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->withBaseUri('https://www.141jav.com')
            ->withDefaultLogging('onefouronejav-client')
            ->build();
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
