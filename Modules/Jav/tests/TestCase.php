<?php

namespace Modules\Jav\tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Modules\Client\Services\Factory;
use Modules\Jav\Models\OnejavReference;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        OnejavReference::truncate();

        $clientMock = \Mockery::mock(ClientInterface::class);
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'new'
            )
            ->andReturn(
                new Response(
                    \Symfony\Component\HttpFoundation\Response::HTTP_OK,
                    [
                        'Content-Type' => 'text/html',
                    ],
                    file_get_contents(__DIR__ . '/Fixtures/Onejav/new_1.html')
                )
            );
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                'popular'
            )
            ->andReturn(
                new Response(
                    \Symfony\Component\HttpFoundation\Response::HTTP_OK,
                    [
                        'Content-Type' => 'text/html',
                    ],
                    file_get_contents(__DIR__ . '/Fixtures/Onejav/popular_1.html')
                )
            );

        $factoryMock = \Mockery::mock(Factory::class);
        $factoryMock->shouldReceive('enableRetries')
            ->andReturnSelf();
        $factoryMock->shouldReceive('make')
            ->andReturn($clientMock);

        app()->instance(Factory::class, $factoryMock);
    }
}
