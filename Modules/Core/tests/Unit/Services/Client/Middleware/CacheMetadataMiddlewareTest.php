<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Services\Client\Middleware;

use ArrayObject;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use JOOservices\Client\Cache\MemoryCache;
use Modules\Core\Services\Client\Middleware\CacheMetadataMiddleware;
use Modules\Core\Services\Client\Middleware\RetryTrackingMiddleware;
use Modules\Core\Tests\TestCase;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

final class CacheMetadataMiddlewareTest extends TestCase
{
    public function test_marks_cache_hit_for_existing_key(): void
    {
        $faker = fake();
        $cache = new MemoryCache;
        $middleware = new CacheMetadataMiddleware($cache, 300, 'memory');
        $url = 'https://'.$faker->domainName().'/api/items?page=1';
        $request = new Request('GET', $url);
        $key = 'http_cache_'.md5('GET '.$url);

        $cache->set($key, ['status' => 200, 'headers' => [], 'body' => '{}'], 300);

        $context = new ArrayObject([
            'cache' => [
                'enabled' => false,
                'hit' => false,
                'key' => null,
                'ttl_sec' => 300,
                'store' => 'memory',
            ],
        ]);
        $middleware(
            $request,
            [RetryTrackingMiddleware::CONTEXT_KEY => $context, 'cache_ttl' => 300],
            fn (RequestInterface $req, array $opts): ResponseInterface => new Response(200)
        );

        $this->assertIsArray($context['cache']);
        $this->assertTrue($context['cache']['enabled']);
        $this->assertTrue($context['cache']['hit']);
    }

    public function test_marks_cache_disabled_for_non_get_requests(): void
    {
        $cache = new MemoryCache;
        $middleware = new CacheMetadataMiddleware($cache, 120, 'memory');
        $request = new Request('POST', 'https://example.test/api/items');
        $context = new ArrayObject([
            'cache' => [
                'enabled' => false,
                'hit' => false,
                'key' => null,
                'ttl_sec' => 120,
                'store' => 'memory',
            ],
        ]);

        $middleware(
            $request,
            [RetryTrackingMiddleware::CONTEXT_KEY => $context],
            fn (RequestInterface $req, array $opts): ResponseInterface => new Response(201)
        );

        $this->assertFalse($context['cache']['enabled']);
        $this->assertFalse($context['cache']['hit']);
        $this->assertSame(120, $context['cache']['ttl_sec']);
    }
}
