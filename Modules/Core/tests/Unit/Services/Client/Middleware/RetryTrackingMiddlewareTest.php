<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services\Client\Middleware;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Modules\Core\Services\Client\Middleware\RetryTrackingMiddleware;
use Modules\Core\Tests\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class RetryTrackingMiddlewareTest extends TestCase
{
    public function test_increments_attempt_per_invocation(): void
    {
        $faker = fake();
        $middleware = new RetryTrackingMiddleware();
        $context = new \ArrayObject(['attempt' => 0, 'retries' => 0, 'marker' => $faker->uuid()]);
        $request = new Request('GET', 'https://'.$faker->domainName().'/items');

        $next = function (RequestInterface $req, array $opts): ResponseInterface {
            return new Response(200, [], 'ok');
        };

        $middleware($request, [RetryTrackingMiddleware::CONTEXT_KEY => $context], $next);
        $middleware($request, [RetryTrackingMiddleware::CONTEXT_KEY => $context], $next);

        $this->assertSame(2, $context['attempt']);
        $this->assertSame(1, $context['retries']);
    }

    public function test_does_not_fail_without_context(): void
    {
        $middleware = new RetryTrackingMiddleware();
        $request = new Request('GET', 'https://example.test');

        $response = $middleware(
            $request,
            [],
            fn (RequestInterface $req, array $opts): ResponseInterface => new Response(200)
        );

        $this->assertSame(200, $response->getStatusCode());
    }
}
