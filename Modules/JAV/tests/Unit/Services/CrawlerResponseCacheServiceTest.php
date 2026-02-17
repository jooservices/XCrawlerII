<?php

namespace Modules\JAV\Tests\Unit\Services;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use Modules\Core\Facades\Config;
use Modules\JAV\Models\CrawlerResponseCache;
use Modules\JAV\Services\CrawlerResponseCacheService;
use Modules\JAV\Tests\TestCase;

class CrawlerResponseCacheServiceTest extends TestCase
{
    public function test_store_and_read_cached_response(): void
    {
        Config::set('onejav', 'crawler_cache_ttl', '3600');

        $service = app(CrawlerResponseCacheService::class);
        $response = new Response(200, ['X-Test' => ['yes']], 'body');

        $service->storeResponse('onejav', 'new', '/new?page=1', $response);
        $cached = $service->getCachedResponse('onejav', 'new', '/new?page=1');

        $this->assertNotNull($cached);
        $this->assertSame(200, $cached->status());
        $this->assertSame('body', (string) $cached->toPsrResponse()->getBody());
    }

    public function test_store_response_skips_non_success_status(): void
    {
        $service = app(CrawlerResponseCacheService::class);
        $response = new Response(404, [], 'not found');

        $service->storeResponse('onejav', 'new', '/new?page=2', $response);

        $this->assertNull($service->getCachedResponse('onejav', 'new', '/new?page=2'));
    }

    public function test_store_html_and_prune_expired(): void
    {
        Carbon::setTestNow('2026-02-17 10:00:00');
        Config::set('missav', 'crawler_cache_ttl', '3600');

        $service = app(CrawlerResponseCacheService::class);
        $service->storeHtml('missav', 'new', '/dm590/en/release', '<html></html>');

        $this->assertSame(1, CrawlerResponseCache::query()->count());

        Carbon::setTestNow('2026-02-17 12:00:01');
        $deleted = $service->pruneExpired();

        $this->assertSame(1, $deleted);
        $this->assertSame(0, CrawlerResponseCache::query()->count());
        Carbon::setTestNow();
    }
}
