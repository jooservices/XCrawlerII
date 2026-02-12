<?php

namespace Modules\JAV\Services\Clients;

use JOOservices\Client\Client\ClientBuilder;
use JOOservices\Client\Client\HttpClient as Client;
use Illuminate\Support\Facades\Cache;

class OnejavClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create()
            ->withBaseUri('https://onejav.com')
            ->withDefaultLogging('onejav-client')
            //->withCache(Cache::store('redis'))
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
