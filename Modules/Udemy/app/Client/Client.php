<?php

namespace Modules\Udemy\Client;

use Exception;
use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\Client\Interfaces\IResponse;
use Modules\Client\Services\Clients\BaseClient;
use Modules\Client\Services\Factory;
use Modules\Udemy\Exceptions\TokenNotFoundException;

class Client extends BaseClient
{
    public const string CONTENT_TYPE = 'application/json, text/plain';

    private string $token;

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
                        'base_uri' => config('udemy.client.base_uri'),
                        'referer' => config('udemy.client.base_uri'),
                        'headers' => [
                            'User-Agent' => $this->getUserAgent(),
                            'Accept' => 'application/json, text/plain',
                        ],
                    ]
                )
            );
    }

    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * @throws Exception
     */
    public function request(
        string $method,
        string $endpoint,
        array $payload = [],
        array $options = []
    ): IResponse {
        if (!isset($this->token)) {
            throw new TokenNotFoundException('Token not set');
        }

        if (app()->environment('testing')) {
            $userAgent = 'testing';
        }

        $options = array_merge(
            $options,
            [
                'headers' => [
                    'User-Agent' => $userAgent ?? $this->getUserAgent(),
                    'Authorization' => "Bearer {$this->token}",
                    'Accept' => 'application/json, text/plain',
                ],
            ]
        );

        return parent::request($method, $endpoint, $payload, $options);
    }
}
