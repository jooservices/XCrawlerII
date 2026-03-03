<?php

declare(strict_types=1);

namespace Modules\JAV\Tests\Unit\Services\Crawling\Client;

use JOOservices\Client\Contracts\HttpClientInterface;
use Modules\Core\Services\Client\Client;
use Modules\Core\Services\Client\ClientFactory;
use Modules\JAV\Services\Crawling\Client\OnejavClient;
use Modules\JAV\Tests\TestCase;
use Mockery;

final class OnejavClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('onejav_new_page1.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI . '/new?page=1', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/new?page=1');

        $this->assertSame($response, $result);
    }

    public function test_get_new_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('onejav_new.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI . '/new', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/new');

        $this->assertSame($response, $result);
    }

    public function test_get_does_not_override_user_agent_when_provided(): void
    {
        $response = $this->getMockResponseWrapper('onejav_new_page1.html');
        $customUa = 'CustomUA/1.0';
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', Mockery::type('string'), Mockery::on(function (array $options) use ($customUa): bool {
                return ($options['headers']['User-Agent'] ?? '') === $customUa;
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/path', ['headers' => ['User-Agent' => $customUa]]);
        $this->assertSame($response, $result);
    }

    public function test_get_does_not_add_user_agent_when_uppercase_user_agent_header_present(): void
    {
        $response = $this->getMockResponseWrapper('onejav_new_page1.html');
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
        $client = new OnejavClient($coreClient);
        $result = $client->get('/path', ['headers' => ['USER-AGENT' => $customUa]]);
        $this->assertSame($response, $result);
    }

    public function test_get_does_not_add_user_agent_when_lowercase_user_agent_header_present(): void
    {
        $response = $this->getMockResponseWrapper('onejav_new_page1.html');
        $customUa = 'LowercaseUA/1.0';
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', Mockery::type('string'), Mockery::on(function (array $options) use ($customUa): bool {
                return ($options['headers']['user-agent'] ?? '') === $customUa;
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/path', ['headers' => ['user-agent' => $customUa]]);
        $this->assertSame($response, $result);
    }

    public function test_get_empty_path_returns_base_url(): void
    {
        $response = $this->getMockResponseWrapper('onejav_new_page1.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI, Mockery::any())
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('');
        $this->assertSame($response, $result);
    }

    public function test_get_date_path_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('onejav_2025-03-03.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI . '/2025/03/03', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/2025/03/03');

        $this->assertSame($response, $result);
    }

    public function test_get_popular_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('onejav_popular.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI . '/popular', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/popular');

        $this->assertSame($response, $result);
    }

    public function test_get_date_with_page_1_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('onejav_2026-03-03_page1.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI . '/2026/03/03?page=1', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/2026/03/03?page=1');

        $this->assertSame($response, $result);
    }

    public function test_get_date_with_page_2_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('onejav_2026-03-03_page2.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI . '/2026/03/03?page=2', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/2026/03/03?page=2');

        $this->assertSame($response, $result);
    }

    public function test_get_date_with_page_3_invokes_client_request_with_built_url(): void
    {
        $response = $this->getMockResponseWrapper('onejav_2026-03-03_page3.html');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->with('GET', OnejavClient::BASE_URI . '/2026/03/03?page=3', Mockery::on(function (array $options): bool {
                return isset($options['headers']['User-Agent']) && $options['headers']['User-Agent'] !== '';
            }))
            ->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/2026/03/03?page=3');

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
        $client = new OnejavClient($coreClient);
        $result = $client->get('/missing');

        $this->assertSame(404, $result->toPsrResponse()->getStatusCode());
        $this->assertSame('Not Found', (string) $result->toPsrResponse()->getBody());
    }

    public function test_get_returns_500_response_when_server_returns_500(): void
    {
        $response = $this->getMockResponseWrapper('', 500, 'Internal Server Error');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')->once()->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/error');

        $this->assertSame(500, $result->toPsrResponse()->getStatusCode());
    }

    public function test_get_throws_when_underlying_client_throws(): void
    {
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->once()
            ->andThrow(new \GuzzleHttp\Exception\ConnectException('Connection refused', new \GuzzleHttp\Psr7\Request('GET', 'https://onejav.com/')));
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);

        $this->expectException(\GuzzleHttp\Exception\ConnectException::class);
        $client->get('/path');
    }

    public function test_get_returns_empty_body_when_server_returns_empty_200(): void
    {
        $response = $this->getMockResponseWrapper('', 200, '');
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')->once()->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/empty');

        $this->assertSame(200, $result->toPsrResponse()->getStatusCode());
        $this->assertSame('', (string) $result->toPsrResponse()->getBody());
    }

    public function test_get_returns_302_with_location_when_server_redirects(): void
    {
        $response = $this->getMockResponseWrapper('', 302, '', ['Location' => 'https://onejav.com/target']);
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')->once()->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/redirect');

        $this->assertSame(302, $result->toPsrResponse()->getStatusCode());
        $this->assertSame('https://onejav.com/target', $result->toPsrResponse()->getHeaderLine('Location'));
    }

    public function test_get_returns_malformed_body_unchanged(): void
    {
        $body = 'not html at all';
        $response = $this->getMockResponseWrapper('', 200, $body, ['Content-Type' => 'text/plain']);
        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')->once()->andReturn($response);
        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);
        $coreClient = $this->app->make(Client::class);
        $client = new OnejavClient($coreClient);
        $result = $client->get('/malformed');

        $this->assertSame(200, $result->toPsrResponse()->getStatusCode());
        $this->assertSame($body, (string) $result->toPsrResponse()->getBody());
    }
}
