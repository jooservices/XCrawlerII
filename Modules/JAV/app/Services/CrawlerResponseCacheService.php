<?php

namespace Modules\JAV\Services;

use Carbon\Carbon;
use GuzzleHttp\Psr7\Response;
use JOOservices\Client\Response\ResponseWrapper;
use Modules\Core\Facades\Config;
use Modules\JAV\Models\CrawlerResponseCache;
use Psr\Http\Message\ResponseInterface;

class CrawlerResponseCacheService
{
    public function resolveTtlSeconds(string $provider): int
    {
        $value = Config::get($provider, 'crawler_cache_ttl', 7200);
        $ttl = (int) $value;

        return $ttl > 0 ? $ttl : 7200;
    }

    public function getCachedResponse(string $provider, string $type, string $url): ?ResponseWrapper
    {
        $cache = $this->getCacheRecord($provider, $type, $url);
        if ($cache === null) {
            return null;
        }

        $status = $cache->status_code ?? 200;
        $headers = $this->decodeHeaders($cache->headers);
        $body = (string) ($cache->body ?? '');

        return $this->wrapResponse(new Response($status, $headers, $body));
    }

    public function getCachedHtml(string $provider, string $type, string $url): ?string
    {
        $cache = $this->getCacheRecord($provider, $type, $url);
        if ($cache === null) {
            return null;
        }

        return (string) ($cache->body ?? '');
    }

    public function storeResponse(string $provider, string $type, string $url, ResponseInterface $response): void
    {
        $status = $response->getStatusCode();
        if ($status < 200 || $status >= 300) {
            return;
        }

        $body = (string) $response->getBody();
        if ($response->getBody()->isSeekable()) {
            $response->getBody()->rewind();
        }

        $headers = json_encode($response->getHeaders());
        $this->storePayload($provider, $type, $url, $status, $headers ?: null, $body);
    }

    public function storeHtml(string $provider, string $type, string $url, string $html): void
    {
        $this->storePayload($provider, $type, $url, 200, null, $html);
    }

    public function pruneExpired(): int
    {
        return CrawlerResponseCache::query()
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', Carbon::now())
            ->delete();
    }

    private function getCacheRecord(string $provider, string $type, string $url): ?CrawlerResponseCache
    {
        $cacheKey = $this->buildCacheKey($provider, $type, $url);

        return CrawlerResponseCache::query()
            ->where('cache_key', $cacheKey)
            ->whereNotNull('expires_at')
            ->where('expires_at', '>', Carbon::now())
            ->first();
    }

    private function storePayload(string $provider, string $type, string $url, int $status, ?string $headers, string $body): void
    {
        $ttl = $this->resolveTtlSeconds($provider);
        $now = Carbon::now();

        CrawlerResponseCache::updateOrCreate(
            ['cache_key' => $this->buildCacheKey($provider, $type, $url)],
            [
                'provider' => $provider,
                'type' => $type,
                'url' => $url,
                'status_code' => $status,
                'headers' => $headers,
                'body' => $body,
                'fetched_at' => $now,
                'expires_at' => $now->copy()->addSeconds($ttl),
            ]
        );
    }

    private function buildCacheKey(string $provider, string $type, string $url): string
    {
        return sha1($provider.'|'.$type.'|'.$url);
    }

    private function decodeHeaders(?string $headers): array
    {
        if ($headers === null || $headers === '') {
            return [];
        }

        $decoded = json_decode($headers, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function wrapResponse(ResponseInterface $response): ResponseWrapper
    {
        return new ResponseWrapper($response);
    }
}
