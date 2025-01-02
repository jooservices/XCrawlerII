<?php

namespace Modules\Jav\Client\MissAv;

use Campo\UserAgent;
use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\Client\Services\Clients\BaseClient;
use Modules\Client\Services\Factory;

class Client extends BaseClient
{
    /**
     * @throws BindingResolutionException
     */
    public function __construct(array $options = [])
    {
        $this->client = app(Factory::class)
            ->enableRetries()
            ->make(
                array_merge(
                    $options,
                    [
                        'base_uri' => config('jav.missav.base_uri'),
                        'referer' => config('jav.missav.base_uri'),
                        'headers' => [
                            'User-Agent' => $this->getUserAgent(),
                            'Accept' => 'text/html; charset=utf-8',
                        ],
                    ]
                )
            );
    }
}
