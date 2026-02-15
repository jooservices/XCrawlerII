<?php

namespace Modules\JAV\Tests\Unit\Services\Clients;

use JOOservices\Client\Contracts\HttpClientInterface as Client;
use Mockery;
use Modules\JAV\Services\Clients\OnejavClient;
use Modules\JAV\Tests\TestCase;

class OnejavClientTest extends TestCase
{
    public function test_get_factory()
    {
        $mockClient = Mockery::mock(Client::class);
        $service = new OnejavClient($mockClient);

        $this->assertSame($mockClient, $service->getFactory());
    }

    public function test_proxies_calls_to_client()
    {
        $mockClient = Mockery::mock(Client::class);
        $response = $this->getMockResponse('onejav_new_15670.html');

        $mockClient->shouldReceive('get')
            ->once()
            ->with('/some-endpoint', ['param' => 'value'])
            ->andReturn($response);

        $service = new OnejavClient($mockClient);

        $result = $service->get('/some-endpoint', ['param' => 'value']);

        $this->assertSame($response, $result);
    }
}
