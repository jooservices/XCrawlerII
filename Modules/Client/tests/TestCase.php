<?php

namespace Modules\Client\Tests;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request;
use Modules\Client\Services\Factory;
use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected string $responseMessage = '';

    protected function useMock()
    {
        $this->responseMessage = $this->faker->sentence;
        $clientMock = \Mockery::mock(ClientInterface::class);
        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                '/success'
            )
            ->andReturn(
                new Response(
                    \Symfony\Component\HttpFoundation\Response::HTTP_OK,
                    [
                        'Content-Type' => 'application/json',
                    ],
                    json_encode([$this->responseMessage])
                )
            );

        $clientMock->shouldReceive('request')
            ->withSomeOfArgs(
                Request::METHOD_GET,
                '/html'
            )
            ->andReturn(
                new Response(
                    \Symfony\Component\HttpFoundation\Response::HTTP_OK,
                    [
                        'Content-Type' => 'text/html',
                    ],
                    $this->faker->randomHtml
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
