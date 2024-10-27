<?php

namespace Modules\Jav\app\Services\Client;

use Campo\UserAgent;
use Modules\Client\Services\Clients\BaseClient;
use Modules\Client\Services\Factory;

class OnejavClient extends BaseClient
{
    public function __construct()
    {
        $this->client = app(Factory::class)
            ->enableRetries()
            ->make([
                'base_uri' => config('jav.onejav.base_uri'),
                'referer' => config('jav.onejav.base_uri'),
                'headers' => [
                    'User-Agent' => $this->getUserAgent(),
                    'Accept' => 'text/html; charset=utf-8',
                ],
            ]);
    }

    private function getUserAgent(): string
    {
        return UserAgent::random([
            'device_type' => 'Desktop',
        ]);
    }
}
