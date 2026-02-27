<?php

declare(strict_types=1);

namespace Modules\Core\Services\Client;

use JOOservices\Client\Client\ClientBuilder;
use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\Client\Resilience\RetryConfig;
use Modules\Core\Services\Client\Contracts\ClientContract;
use Modules\Core\Services\Client\Logging\HttpLogSanitizer;
use Modules\Core\Services\Client\Logging\MongoHttpLogWriter;
use Modules\Core\Services\Client\Middleware\CacheMetadataMiddleware;
use Modules\Core\Services\Client\Middleware\RetryTrackingMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;

final class Client implements ClientContract
{
    public function __construct(
        private readonly HttpLogSanitizer $sanitizer,
        private readonly MongoHttpLogWriter $logWriter,
        private readonly CacheInterface $cache,
        private readonly int $timeoutSec = 20,
        private readonly int $connectTimeoutSec = 8,
        private readonly int $defaultMaxAttempts = 3,
        private readonly int $defaultCacheTtlSec = 300,
        private readonly string $cacheStore = 'default',
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        $context = new \ArrayObject([
            'attempt' => 0,
            'retries' => 0,
            'cache' => [
                'enabled' => false,
                'hit' => false,
                'key' => null,
                'ttl_sec' => null,
                'store' => $this->cacheStore,
            ],
        ]);

        $options[RetryTrackingMiddleware::CONTEXT_KEY] = $context;
        $maxAttempts = (int) ($options['max_attempts'] ?? $this->defaultMaxAttempts);
        $maxAttempts = max(1, $maxAttempts);
        $options['cache_ttl'] = (int) ($options['cache_ttl'] ?? $this->defaultCacheTtlSec);
        $start = microtime(true);
        $response = null;
        $error = null;

        try {
            $client = $this->buildClient($maxAttempts);
            $response = $client->request(strtoupper($method), $url, $options)->toPsrResponse();

            return $response;
        } catch (Throwable $exception) {
            $error = $exception;
            throw $exception;
        } finally {
            $durationMs = (int) round((microtime(true) - $start) * 1000);
            $this->writeLog(
                method: strtoupper($method),
                url: $url,
                options: $options,
                context: $context,
                response: $response,
                error: $error,
                durationMs: $durationMs,
                maxAttempts: $maxAttempts,
            );
        }
    }

    private function buildClient(int $maxAttempts): HttpClientInterface
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

    /**
     * @param array<string, mixed> $options
     */
    private function writeLog(
        string $method,
        string $url,
        array $options,
        \ArrayObject $context,
        ?ResponseInterface $response,
        ?Throwable $error,
        int $durationMs,
        int $maxAttempts,
    ): void {
        $parsedUrl = parse_url($url);
        $site = (string) ($parsedUrl['host'] ?? '');
        $path = (string) ($parsedUrl['path'] ?? '/');

        $requestHeaders = $this->sanitizer->sanitizeHeaders($options['headers'] ?? []);
        $requestBody = $this->sanitizer->sanitizeRequestBody($options);

        $responseHeaders = $response ? $this->sanitizer->sanitizeHeaders($response->getHeaders()) : [];
        $responseBody = $response ? $this->sanitizer->sanitizeResponseBody($response) : $this->emptyBody();

        $attempt = max(1, (int) ($context['attempt'] ?? 1));
        $retries = max(0, $attempt - 1);
        $status = $response?->getStatusCode() ?? 0;
        $cache = $context['cache'] ?? [
            'enabled' => false,
            'hit' => false,
            'key' => null,
            'ttl_sec' => null,
            'store' => $this->cacheStore,
        ];

        $payload = [
            'ts' => new \DateTimeImmutable(),
            'site' => $site,
            'method' => $method,
            'path' => $path,
            'url' => $url,
            'status' => $status,
            'duration_ms' => $durationMs,
            'attempt' => $attempt,
            'max_attempts' => $maxAttempts,
            'request' => [
                'headers' => $requestHeaders,
                'body_preview' => $requestBody['body_preview'],
                'body_sha1' => $requestBody['body_sha1'],
                'body_truncated' => $requestBody['body_truncated'],
                'size_bytes' => $requestBody['size_bytes'],
            ],
            'response' => [
                'headers' => $responseHeaders,
                'body_preview' => $responseBody['body_preview'],
                'body_sha1' => $responseBody['body_sha1'],
                'body_truncated' => $responseBody['body_truncated'],
                'size_bytes' => $responseBody['size_bytes'],
            ],
            'cache' => $cache,
            'error' => $error ? [
                'type' => $this->resolveErrorType($error),
                'code' => (string) $error->getCode(),
                'message' => $error->getMessage(),
            ] : null,
            'correlation_id' => $requestHeaders['x-correlation-id'] ?? null,
            'trace_id' => $requestHeaders['x-trace-id'] ?? null,
            'tags' => is_array($options['tags'] ?? null) ? $options['tags'] : [],
            'task_id' => is_string($options['task_id'] ?? null) ? $options['task_id'] : null,
            'job_id' => is_string($options['job_id'] ?? null) ? $options['job_id'] : null,
        ];

        $payload['retries'] = $retries;

        $this->logWriter->write($payload);
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyBody(): array
    {
        return [
            'body_preview' => null,
            'body_sha1' => sha1(''),
            'body_truncated' => false,
            'size_bytes' => 0,
        ];
    }

    private function resolveErrorType(Throwable $error): string
    {
        $class = strtolower($error::class);

        if (str_contains($class, 'timeout')) {
            return 'timeout';
        }

        if (str_contains($class, 'network')) {
            return 'network';
        }

        return 'unknown';
    }
}
