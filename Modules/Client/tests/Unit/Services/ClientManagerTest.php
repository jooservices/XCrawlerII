<?php

namespace Modules\Client\Tests\Unit\Services;

use Modules\Client\Exceptions\ClientNotFound;
use Modules\Client\Services\ClientManager;
use Modules\Client\Services\Clients\BaseClient;
use Modules\Client\Tests\TestCase;

class ClientManagerTest extends TestCase
{
    public function testGetClientSuccess(): void
    {
        $this->assertInstanceOf(
            BaseClient::class,
            app(ClientManager::class)->getClient(BaseClient::class)
        );
    }

    public function testGetClientNotFound(): void
    {
        $this->expectException(ClientNotFound::class);

        app(ClientManager::class)->getClient($this->faker->text);
    }
}
