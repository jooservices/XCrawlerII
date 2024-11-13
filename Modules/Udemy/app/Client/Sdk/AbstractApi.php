<?php

namespace Modules\Udemy\Client\Sdk;

use Modules\Udemy\Client\Client;

abstract class AbstractApi
{
    public function __construct(protected Client $client)
    {
    }

    abstract protected function getEndpoint(string $path): string;
}
