<?php

declare(strict_types=1);

namespace Modules\Core\Services\Client\Middleware;

use Closure;
use JOOservices\Client\Contracts\MiddlewareInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

final class CacheMetadataMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $defaultTtlSec,
        private readonly string $storeName,
    ) {}

    public function __invoke(RequestInterface $request, array $options, Closure $next): ResponseInterface
    {
        $context = $options[RetryTrackingMiddleware::CONTEXT_KEY] ?? null;
        $cacheEnabled = $request->getMethod() === 'GET' && (($options['cache_enabled'] ?? true) === true);
        $ttlSec = (int) ($options['cache_ttl'] ?? $this->defaultTtlSec);
        $cacheKey = $this->cacheKey($request);
        $hit = false;

        if ($cacheEnabled) {
            $hit = $this->cache->has($cacheKey);
        }

        if ($context instanceof \ArrayObject) {
            $context['cache'] = [
                'enabled' => $cacheEnabled,
                'hit' => $hit,
                'key' => $cacheKey,
                'ttl_sec' => $ttlSec,
                'store' => $this->storeName,
            ];
        }

        return $next($request, $options);
    }

    private function cacheKey(RequestInterface $request): string
    {
        return 'http_cache_'.md5($request->getMethod().' '.$request->getUri());
    }
}
