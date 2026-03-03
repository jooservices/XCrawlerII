<?php

declare(strict_types=1);

namespace Modules\Core\Services\Client;

use JOOservices\Client\Client\ClientBuilder;
use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\Client\Resilience\RetryConfig;
use Modules\Core\Services\Client\Middleware\CacheMetadataMiddleware;
use Modules\Core\Services\Client\Middleware\RetryTrackingMiddleware;
use Psr\SimpleCache\CacheInterface;

class ClientFactory
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly int $timeoutSec = 20,
        private readonly int $connectTimeoutSec = 8,
        private readonly int $defaultCacheTtlSec = 300,
        private readonly string $cacheStore = 'default',
    ) {
    }

    public function create(int $maxAttempts): HttpClientInterface
    {
        return ClientBuilder::create()
            ->withTimeout($this->timeoutSec)
            ->withConnectTimeout($this->connectTimeoutSec)
            ->withRetry(new RetryConfig(maxAttempts: $maxAttempts))
            ->withMiddleware(new RetryTrackingMiddleware(), 'retry_tracking')
            ->withMiddleware(
                new CacheMetadataMiddleware($this->cache, $this->defaultCacheTtlSec, $this->cacheStore),
                'cache_meta'
            )
            ->withCache($this->cache, $this->defaultCacheTtlSec)
            ->build();
    }
}
