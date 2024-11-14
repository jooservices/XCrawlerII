<?php

namespace Modules\Client\Services;

use Modules\Client\Exceptions\ClientNotFound;
use Modules\Client\Interfaces\IClient;
use Modules\Client\Services\Clients\BaseClient;
use Modules\Jav\Onejav\Client as OnejavClient;
use Modules\Udemy\Client\Client as UdemyClient;

class ClientManager
{
    private array $classes = [];

    public function __construct()
    {
        $this->register(BaseClient::class);
        $this->register(OnejavClient::class);
        $this->register(UdemyClient::class);
    }

    public function register(string $class): static
    {
        $this->classes[] = $class;

        return $this;
    }

    public function getClasses(): array
    {
        return $this->classes;
    }

    /**
     * @TODO Support IClient with arguments
     */
    public function getClient(string $client): IClient
    {
        if (!in_array($client, $this->classes)) {
            throw new ClientNotFound("Client [$client] not found");
        }

        return app($client);
    }
}
