<?php

namespace Modules\Client\Tests\Unit\Services;

use Modules\Client\Services\ClientManager;
use Modules\Client\Services\Clients\BaseClient;
use Modules\Client\Services\Clients\ResponseData\DomResponseData;
use Modules\Client\Services\Clients\ResponseData\JsonResponseData;
use Modules\Client\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TestBaseResponse extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();

        $this->useMock();
    }

    public function testSuccessResponse()
    {
        $manager = app(ClientManager::class);
        $client = $manager->getClient(BaseClient::class);

        $this->assertInstanceOf(BaseClient::class, $client);
        $response = $client->request(Request::METHOD_GET, '/success', []);
        $this->assertEquals(
            '["' . $this->responseMessage . '"]',
            $response->getBody()
        );
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($response->isSuccess());
        $this->assertInstanceOf(JsonResponseData::class, $response->parseBody());

        $this->assertDatabaseHas(
            'request_logs',
            [
                'method' => Request::METHOD_GET,
                'endpoint' => '/success',
                'status_code' => Response::HTTP_OK,
            ],
            'mongodb'
        );
    }

    public function testResponseWithDom()
    {
        $manager = app(ClientManager::class);
        $client = $manager->getClient(BaseClient::class);

        $this->assertInstanceOf(BaseClient::class, $client);
        $response = $client->request(Request::METHOD_GET, '/html', []);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertTrue($response->isSuccess());
        $this->assertInstanceOf(DomResponseData::class, $response->parseBody());
    }
}
