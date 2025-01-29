<?php

namespace Modules\StableDiffusion\Client;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\Client\Interfaces\IResponse;
use Modules\Client\Services\Clients\BaseClient;
use Modules\Client\Services\Factory;
use Modules\Udemy\Exceptions\TokenNotFoundException;

class Client extends BaseClient
{
    public const string CONTENT_TYPE = 'application/json, text/plain';

    /**
     * @throws BindingResolutionException
     * @throws Exception
     */
    public function __construct(array $options = [])
    {
        $this->client = app(Factory::class)
            ->enableRetries()
            ->make(
                array_merge(
                    $options,
                    [
                        'base_uri' => config('stablediffusion.client.base_uri'),
                        'referer' => config('stablediffusion.client.base_uri'),
                        'headers' => [
                            'Accept' => 'application/json, text/plain',
                        ],
                    ]
                )
            );
    }
}
