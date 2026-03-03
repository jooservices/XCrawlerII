<?php

declare(strict_types=1);

namespace Modules\JAV\Tests;

use JOOservices\Client\Contracts\ResponseWrapperInterface;
use Modules\JAV\Tests\Stubs\ResponseWrapperStub;
use Modules\Core\Tests\TestCase as BaseTestCase;
use Psr\Http\Message\ResponseInterface;

abstract class TestCase extends BaseTestCase
{
    protected function loadFixture(string $path): string
    {
        $fullPath = __DIR__ . '/Fixtures/' . $path;
        if (! is_file($fullPath)) {
            throw new \InvalidArgumentException("Fixture not found: {$path}");
        }

        return file_get_contents($fullPath);
    }

    protected function getMockResponse(string $path): ResponseInterface
    {
        $content = $this->loadFixture($path);

        return new \GuzzleHttp\Psr7\Response(200, [], \GuzzleHttp\Psr7\Utils::streamFor($content));
    }

    /**
     * Build a response wrapper from a fixture file or from status/body/headers.
     * - When $path is non-empty: load fixture from Fixtures/$path, return 200 with that body.
     * - When $path is empty: use $statusCode, $body, $headers (for 404, 500, redirects, etc.).
     */
    protected function getMockResponseWrapper(string $path = '', int $statusCode = 200, string $body = '', array $headers = []): ResponseWrapperInterface
    {
        if ($path !== '') {
            $body = $this->loadFixture($path);
            $statusCode = 200;
            $headers = [];
        }

        $res = new \GuzzleHttp\Psr7\Response(
            $statusCode,
            $headers,
            \GuzzleHttp\Psr7\Utils::streamFor($body)
        );

        return new ResponseWrapperStub($res);
    }
}
