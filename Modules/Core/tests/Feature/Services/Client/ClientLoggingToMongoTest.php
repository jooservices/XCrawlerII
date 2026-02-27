<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Services\Client;

use Modules\Core\Models\ClientLog;
use Modules\Core\Services\Client\Contracts\ClientContract;
use Modules\Core\Tests\TestCase;
use MongoDB\Driver\Command;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Query;

final class ClientLoggingToMongoTest extends TestCase
{
    /**
     * @var resource|null
     */
    private $serverProcess = null;

    private string $routerFile = '';

    private int $serverPort = 19091;

    private string $testRunId = '';

    private Manager $mongo;

    private bool $initialized = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testRunId = 'test_run_'.fake()->uuid();
        $this->mongo = new Manager((string) env('MONGO_URI', 'mongodb://127.0.0.1:27017'));
        $this->assertMongoAvailable();
        $this->bootLocalHttpServer();
        $this->initialized = true;
    }

    protected function tearDown(): void
    {
        if ($this->initialized) {
            $this->shutdownLocalHttpServer();
        }

        parent::tearDown();
    }

    public function test_client_logs_sanitized_payload_retry_and_cache_metadata_to_mongo(): void
    {
        /** @var ClientContract $client */
        $client = $this->app->make(ClientContract::class);
        $baseUrl = "http://127.0.0.1:{$this->serverPort}/ok";
        $correlationId = fake()->uuid();

        $client->request('GET', $baseUrl, [
            'headers' => [
                'Authorization' => 'Bearer '.fake()->sha1(),
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ],
            'tags' => [$this->testRunId],
            'task_id' => 'task_'.fake()->uuid(),
            'job_id' => 'job_'.fake()->uuid(),
            'cache_ttl' => 120,
            'max_attempts' => 3,
        ]);

        $client->request('GET', $baseUrl, [
            'headers' => [
                'Authorization' => 'Bearer '.fake()->sha1(),
                'X-Correlation-ID' => $correlationId,
                'Accept' => 'application/json',
            ],
            'tags' => [$this->testRunId],
            'cache_ttl' => 120,
        ]);

        $client->request('GET', "http://127.0.0.1:{$this->serverPort}/fail", [
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

    private function bootLocalHttpServer(): void
    {
        $this->routerFile = storage_path('framework/testing/core-client-router.php');
        $routerDir = dirname($this->routerFile);

        if (! is_dir($routerDir)) {
            mkdir($routerDir, 0777, true);
        }

        file_put_contents($this->routerFile, <<<'PHP'
<?php
header('Content-Type: application/json');
if ($_SERVER['REQUEST_URI'] === '/ok') {
    echo json_encode(['ok' => true, 'token' => 'server-secret-token']);
    return;
}
if ($_SERVER['REQUEST_URI'] === '/fail') {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'server error']);
    return;
}
http_response_code(404);
echo json_encode(['ok' => false]);
PHP);

        $command = sprintf(
            'php -S 127.0.0.1:%d %s',
            $this->serverPort,
            escapeshellarg($this->routerFile)
        );

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', storage_path('logs/core-client-test-server.log'), 'a'],
            2 => ['file', storage_path('logs/core-client-test-server-error.log'), 'a'],
        ];

        $this->serverProcess = proc_open($command, $descriptorSpec, $pipes);
        usleep(300000);

        if (! is_resource($this->serverProcess)) {
            $this->fail('Unable to start local HTTP test server process.');
        }

        $status = proc_get_status($this->serverProcess);
        if (! ($status['running'] ?? false)) {
            $this->fail('Local HTTP server is blocked in this environment.');
        }
    }

    private function shutdownLocalHttpServer(): void
    {
        if (is_resource($this->serverProcess)) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
        }

        if ($this->routerFile !== '' && file_exists($this->routerFile)) {
            unlink($this->routerFile);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchDocsByTag(string $tag): array
    {
        $namespace = (string) env('MONGO_DB', 'xcrawler').'.'.ClientLog::COLLECTION;
        $query = new Query(['tags' => $tag], ['sort' => ['ts' => -1]]);
        $cursor = $this->mongo->executeQuery($namespace, $query);
        $docs = [];

        foreach ($cursor as $doc) {
            $docs[] = json_decode(json_encode($doc, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        }

        return $docs;
    }

    private function assertMongoAvailable(): void
    {
        try {
            $command = new Command(['ping' => 1]);
            $this->mongo->executeCommand((string) env('MONGO_DB', 'xcrawler'), $command);
        } catch (\Throwable $throwable) {
            $this->fail('MongoDB is not reachable in current environment: '.$throwable->getMessage());
        }
    }

    /**
     * @param array<int, array<string, mixed>> $docs
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
