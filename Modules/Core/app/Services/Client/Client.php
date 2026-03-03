<?php

declare(strict_types=1);

namespace Modules\Core\Services\Client;

use ArrayObject;
use DateTimeImmutable;
use JOOservices\Client\Contracts\ResponseWrapperInterface;
use Modules\Core\Models\MongoDb\ClientLog;
use Modules\Core\Services\Client\Logging\HttpLogSanitizer;
use Modules\Core\Services\Client\Middleware\RetryTrackingMiddleware;
use Throwable;

final class Client
{
    public function __construct(
        private readonly HttpLogSanitizer $sanitizer,
        private readonly int $defaultMaxAttempts = 3,
        private readonly string $cacheStore = 'default',
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function request(string $method, string $url, array $options = []): ResponseWrapperInterface
    {
        $context = new ArrayObject([
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
        $options['cache_ttl'] = (int) ($options['cache_ttl'] ?? 300);
        $start = microtime(true);
        $response = null;
        $error = null;

        try {
            $factory = app(ClientFactory::class);
            $client = $factory->create($maxAttempts);
            $response = $client->request(strtoupper($method), $url, $options);

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

    /**
     * @param  array<string, mixed>  $options
     */
    private function writeLog(
        string $method,
        string $url,
        array $options,
        ArrayObject $context,
        ?ResponseWrapperInterface $response,
        ?Throwable $error,
        int $durationMs,
        int $maxAttempts,
    ): void {
        $parsedUrl = parse_url($url);
        $site = (string) ($parsedUrl['host'] ?? '');
        $path = (string) ($parsedUrl['path'] ?? '/');

        $requestHeaders = $this->sanitizer->sanitizeHeaders($options['headers'] ?? []);
        $requestBody = $this->sanitizer->sanitizeRequestBody($options);

        $psrResponse = $response?->toPsrResponse();
        $responseHeaders = $psrResponse ? $this->sanitizer->sanitizeHeaders($psrResponse->getHeaders()) : [];
        $responseBody = $psrResponse ? $this->sanitizer->sanitizeResponseBody($psrResponse) : $this->emptyBody();

        $attempt = max(1, (int) ($context['attempt'] ?? 1));
        $retries = max(0, $attempt - 1);
        $status = $psrResponse?->getStatusCode() ?? 0;
        $cache = $context['cache'] ?? [
            'enabled' => false,
            'hit' => false,
            'key' => null,
            'ttl_sec' => null,
            'store' => $this->cacheStore,
        ];

        $payload = [
            'ts' => new DateTimeImmutable(),
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

        ClientLog::create(ClientLog::fromHttpLifecycle($payload));
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
