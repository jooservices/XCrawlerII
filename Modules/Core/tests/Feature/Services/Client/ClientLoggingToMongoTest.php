<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Services\Client;

use GuzzleHttp\Psr7\Response;
use JOOservices\Client\Contracts\HttpClientInterface;
use JOOservices\Client\Contracts\ResponseWrapperInterface;
use Modules\Core\Models\MongoDb\ClientLog;
use Modules\Core\Services\Client\Client;
use Modules\Core\Services\Client\ClientFactory;
use Modules\Core\Tests\TestCase;
use Mockery;

/**
 * Verifies that the Core HTTP Client (Modules\Core\Services\Client\Client) logs each
 * request/response to MongoDB (ClientLog): sanitized headers/body, status, retry/cache
 * metadata. Mocks ClientFactory and HttpClientInterface; no real HTTP server. Requires MongoDB.
 */
final class ClientLoggingToMongoTest extends TestCase
{
    private string $testRunId = '';

    protected function setUp(): void
    {
        parent::setUp();

        $this->testRunId = 'test_run_' . fake()->uuid();
        $this->assertMongoAvailable();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_client_logs_sanitized_payload_retry_and_cache_metadata_to_mongo(): void
    {
        $psr200 = new Response(200, ['Content-Type' => 'application/json'], '{"ok":true}');
        $psr500 = new Response(500, [], '{"ok":false}');

        $wrapper200 = Mockery::mock(ResponseWrapperInterface::class);
        $wrapper200->shouldReceive('toPsrResponse')->andReturn($psr200);
        $wrapper500 = Mockery::mock(ResponseWrapperInterface::class);
        $wrapper500->shouldReceive('toPsrResponse')->andReturn($psr500);

        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')
            ->andReturn($wrapper200, $wrapper200, $wrapper500);

        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);

        /** @var Client $client */
        $client = app(Client::class);
        $domain = fake()->domainName();
        $baseUrl = "http://{$domain}/ok";
        $failUrl = "http://{$domain}/fail";
        $correlationId = fake()->uuid();

        $client->request('GET', $baseUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . fake()->sha1(),
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ],
            'tags' => [$this->testRunId],
            'task_id' => 'task_' . fake()->uuid(),
            'job_id' => 'job_' . fake()->uuid(),
            'cache_ttl' => 120,
            'max_attempts' => 3,
        ]);

        $client->request('GET', $baseUrl, [
            'headers' => [
                'Authorization' => 'Bearer ' . fake()->sha1(),
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ],
            'tags' => [$this->testRunId],
            'cache_ttl' => 120,
        ]);

        $client->request('GET', $failUrl, [
            'headers' => ['X-Correlation-ID' => $correlationId],
            'tags' => [$this->testRunId],
            'max_attempts' => 3,
        ]);

        $docs = $this->fetchDocsByTag($this->testRunId);

        $this->assertGreaterThanOrEqual(3, count($docs));

        $successDoc = $this->findDocByStatus($docs, 200);
        $failedDoc = $this->findDocByStatus($docs, 500);

        $this->assertNotNull($successDoc);
        $this->assertNotNull($failedDoc);

        $this->assertSame('[REDACTED]', (string) ($successDoc['request']['headers']['authorization'] ?? ''));
        $this->assertArrayHasKey('cache', $successDoc);
        $this->assertArrayHasKey('hit', $successDoc['cache']);
        $this->assertArrayHasKey('attempt', $failedDoc);
        $this->assertArrayHasKey('retries', $failedDoc);
        $this->assertGreaterThanOrEqual(1, (int) $failedDoc['attempt']);
        $this->assertSame(max(0, ((int) $failedDoc['attempt']) - 1), (int) $failedDoc['retries']);
    }

    public function test_client_log_preserves_xss_like_url_as_text_in_mongo_document(): void
    {
        $psr200 = new Response(200, ['Content-Type' => 'application/json'], '{}');
        $wrapper200 = Mockery::mock(ResponseWrapperInterface::class);
        $wrapper200->shouldReceive('toPsrResponse')->andReturn($psr200);

        $mockHttp = Mockery::mock(HttpClientInterface::class);
        $mockHttp->shouldReceive('request')->andReturn($wrapper200);

        $mockFactory = Mockery::mock(ClientFactory::class);
        $mockFactory->shouldReceive('create')->andReturn($mockHttp);

        $this->app->instance(ClientFactory::class, $mockFactory);

        /** @var Client $client */
        $client = app(Client::class);
        $payloadTag = $this->testRunId . '-xss';
        $xssQuery = rawurlencode('<script>alert(1)</script>');
        $domain = fake()->domainName();
        $url = "http://{$domain}/ok?next=" . $xssQuery;

        $client->request('GET', $url, [
            'headers' => ['Accept' => 'application/json'],
            'tags' => [$payloadTag],
        ]);

        $docs = $this->fetchDocsByTag($payloadTag);
        $this->assertNotEmpty($docs);
        $doc = $docs[0];
        $this->assertStringContainsString($xssQuery, (string) ($doc['url'] ?? ''));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchDocsByTag(string $tag): array
    {
        return ClientLog::query()
            ->orderByDesc('ts')
            ->limit(200)
            ->get()
            ->map(static fn (ClientLog $doc): array => $doc->getAttributes())
            ->filter(static fn (array $doc): bool => in_array($tag, (array) ($doc['tags'] ?? []), true))
            ->values()
            ->all();
    }

    private function assertMongoAvailable(): void
    {
        try {
            ClientLog::query()->limit(1)->get();
        } catch (\Throwable $throwable) {
            $this->fail('MongoDB is not reachable in current environment: ' . $throwable->getMessage());
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $docs
     * @return array<string, mixed>|null
     */
    private function findDocByStatus(array $docs, int $status): ?array
    {
        foreach ($docs as $doc) {
            if ((int) ($doc['status'] ?? -1) === $status) {
                return $doc;
            }
        }

        return null;
    }
}
