<?php

namespace Modules\Client\Tests\Unit\Services;

use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Contracts\Container\BindingResolutionException;
use Modules\Client\Services\Factory;
use Modules\Client\Tests\TestCase;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class FactoryTest extends TestCase
{
    /**
     * @throws BindingResolutionException
     */
    public function testMakeClient()
    {
        $this->assertInstanceOf(
            ClientInterface::class,
            app(Factory::class)->make()
        );
    }

    /**
     * @throws GuzzleException
     * @throws BindingResolutionException
     */
    public function testMockSuccess()
    {
        $factory = app(Factory::class);
        $text = $this->faker->sentence;
        $client = $factory
            ->appendResponse(ResponseAlias::HTTP_OK, $text)
            ->make();

        $response = $client->get($this->faker->url);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($text, $response->getBody()->getContents());
    }

    /**
     * @throws GuzzleException
     * @throws BindingResolutionException
     */
    public function testMockWithException()
    {
        $factory = app(Factory::class);
        $client = $factory
            ->appendException('Exception', SymfonyRequest::METHOD_GET, $this->faker->url)
            ->make();

        $this->expectException(Exception::class);
        $client->get($this->faker->url);
    }

    /**
     * @throws GuzzleException
     * @throws BindingResolutionException
     */
    public function testEnableHistory(): void
    {
        $factory = app(Factory::class);
        $text = $this->faker->sentence;
        $client = $factory
            ->appendResponse(ResponseAlias::HTTP_OK, $text)
            ->enableHistory()
            ->make();

        $response = $client->request(SymfonyRequest::METHOD_GET, $this->faker->url);
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals($text, $response->getBody()->getContents());

        $history = $factory->getHistories()[0];

        $this->assertInstanceOf(Request::class, $history[0]['request']);
        $this->assertInstanceOf(Response::class, $history[0]['response']);
    }

    /**
     * @throws GuzzleException|BindingResolutionException
     */
    public function testEnableHistoryCount(): void
    {
        $factory = app(Factory::class);
        $text = $this->faker->sentence;
        $client = $factory
            ->appendResponse(ResponseAlias::HTTP_OK, $text)
            ->appendResponse(ResponseAlias::HTTP_OK, $text)
            ->enableHistory()
            ->make();

        $client->request(SymfonyRequest::METHOD_GET, $this->faker->url);
        $client->request(SymfonyRequest::METHOD_GET, $this->faker->url);

        $this->assertCount(2, $factory->getHistories()[0]);
    }

    /**
     * @throws GuzzleException
     * @throws BindingResolutionException
     */
    public function testEnableRetries(): void
    {
        $factory = app(Factory::class);
        $text = $this->faker->sentence;
        $client = $factory
            ->appendResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, $text)
            ->appendResponse(ResponseAlias::HTTP_INTERNAL_SERVER_ERROR, $text)
            ->appendResponse(ResponseAlias::HTTP_OK, $text)
            ->enableRetries()
            ->make();

        $response = $client->request(SymfonyRequest::METHOD_GET, $this->faker->url);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
