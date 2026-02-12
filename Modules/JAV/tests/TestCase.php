<?php

namespace Modules\JAV\Tests;

use Tests\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function loadFixture(string $path): string
    {
        return file_get_contents(__DIR__ . '/Fixtures/' . $path);
    }

    protected function getMockResponse(string $path): \JOOservices\Client\Response\ResponseWrapper
    {
        $content = $this->loadFixture($path);

        $stream = \Mockery::mock(\Psr\Http\Message\StreamInterface::class);
        $stream->shouldReceive('getContents')->andReturn($content);
        $stream->shouldReceive('__toString')->andReturn($content);

        $psrResponse = \Mockery::mock(\Psr\Http\Message\ResponseInterface::class);
        $psrResponse->shouldReceive('getBody')->andReturn($stream);

        return new \JOOservices\Client\Response\ResponseWrapper($psrResponse);
    }
}
