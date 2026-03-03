<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Services\Crawling\Client;

use JOOservices\Client\Contracts\HttpClientInterface;
use Modules\Core\Services\Client\Client;
use Modules\Core\Services\Client\ClientFactory;
use Modules\JAV\Services\Crawling\Client\FfJavClient;
use Modules\JAV\Tests\TestCase;
use Mockery;

final class FfJavClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_new_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('ffjav_new.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', FfJavClient::BASE_URI . '/new', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new FfJavClient($coreClient);
        $result = $client->get('/new');

        $this->assertSame($response, $result);
    }

    public function test_get_date_path_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('ffjav_2025-03-03.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', FfJavClient::BASE_URI . '/2025/03/03', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new FfJavClient($coreClient);
        $result = $client->get('/2025/03/03');

        $this->assertSame($response, $result);
    }

    public function test_get_popular_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('ffjav_popular.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', FfJavClient::BASE_URI . '/popular', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new FfJavClient($coreClient);
        $result = $client->get('/popular');

        $this->assertSame($response, $result);
    }

    public function test_get_does_not_add_user_agent_when_uppercase_user_agent_header_present(): void
    {
        $response = $this->getMockResponseWrapper('ffjav_new.html');
        $customUa = 'UppercaseUA/1.0';
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', Mockery::type('string'), Mockery::on(function (array $options) use ($customUa): bool {
                return ($options['headers']['USER-AGENT'] ?? '') === $customUa;
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new FfJavClient($coreClient);
        $result = $client->get('/path', ['headers' => ['USER-AGENT' => $customUa]]);
        $this->assertSame($response, $result);
    }

    public function test_get_returns_404_response_when_server_returns_404(): void
    {
        $response = $this->getMockResponseWrapper('', 404, 'Not Found');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')->once()->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new FfJavClient($coreClient);
        $result = $client->get('/missing');

        $this->assertSame(404, $result->toPsrResponse()->getStatusCode());
    }

    public function test_get_throws_when_underlying_client_throws(): void
    {
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->andThrow(new \GuzzleHttp\Exception\ConnectException('Connection refused', new \GuzzleHttp\Psr7\Request('GET', FfJavClient::BASE_URI)));
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new FfJavClient($coreClient);

        $this->expectException(\GuzzleHttp\Exception\ConnectException::class);
        $client->get('/path');
    }
}
