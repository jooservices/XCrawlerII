<?php

namespace Modules\Client\Tests\Unit\Services;

use Modules\Client\Services\ClientManager;
use Modules\Client\Services\Clients\BaseClient;
use Modules\Client\Services\Clients\ResponseData\DomResponseData;
use Modules\Client\Services\Clients\ResponseData\JsonResponseData;
use Modules\Client\Tests\TestCase;
use Modules\Client\Zeus\Wishes\BaseClientWish;
use Modules\Core\Zeus\ZeusService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestBaseClient extends TestCase
{
    private BaseClient $client;

    protected function setUp(): void
    {
        parent::setUp();

        app(ZeusService::class)->wish(BaseClientWish::class);

        $this->client = app(ClientManager::class)->getClient(BaseClient::class);
    }

    public function test_success_json_response(): void
    {
        $this->assertInstanceOf(BaseClient::class, $this->client);
        $response = $this->client->request(Request::METHOD_GET, '/json', []);
        $this->assertEquals('["json"]', $response->getBody());
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($response->isSuccess());
        $this->assertInstanceOf(JsonResponseData::class, $response->parseBody());

        $this->assertDatabaseHas(
            'request_logs',
            [
                'method' => Request::METHOD_GET,
                'endpoint' => '/json',
                'status_code' => Response::HTTP_OK,
            ],
            'mongodb'
        );
    }

    public function test_response_with_dom(): void
    {
        $response = $this->client->request(Request::METHOD_GET, '/html', []);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($response->isSuccess());
        $this->assertInstanceOf(DomResponseData::class, $response->parseBody());
    }
}
