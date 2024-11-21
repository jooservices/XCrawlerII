<?php

namespace Modules\Client\Tests\Unit\Services;

use Modules\Client\Exceptions\ClientNotFound;
use Modules\Client\Services\ClientManager;
use Modules\Client\Services\Clients\BaseClient;
use Modules\Client\Tests\TestCase;

class ClientManagerTest extends TestCase
{
    public function test_get_client_success(): void
    {
        $this->assertInstanceOf(
            BaseClient::class,
            app(ClientManager::class)->getClient(BaseClient::class)
        );
    }

    public function test_get_client_not_found(): void
    {
        $this->expectException(ClientNotFound::class);

        app(ClientManager::class)->getClient($this->faker->text);
    }
}
