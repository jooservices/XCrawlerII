<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Feature\Logging;

use Illuminate\Support\Facades\Log;
use Modules\Core\Models\MongoDb\Log as LogModel;
use Modules\Core\Tests\TestCase;

final class MongoLogChannelTest extends TestCase
{
    private string $testRunId = '';

    protected function setUp(): void
    {
        parent::setUp();
        $this->testRunId = 'test_run_' . fake()->uuid();
        $this->assertMongoAvailable();
    }

    private function assertMongoAvailable(): void
    {
        try {
            LogModel::query()->limit(1)->get();
        } catch (\Throwable $e) {
            $this->markTestSkipped('MongoDB is not reachable: ' . $e->getMessage());
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fetchLogsByTestRunId(): array
    {
        return LogModel::query()
            ->where('context.test_run_id', $this->testRunId)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(static fn (LogModel $log): array => $log->getAttributes())
            ->values()
            ->all();
    }

    public function test_mongodb_channel_writes_document_to_logs_collection(): void
    {
        $message = 'MongoLogChannelTest message ' . $this->testRunId;
        Log::info($message, ['test_run_id' => $this->testRunId]);

        $docs = $this->fetchLogsByTestRunId();
        $this->assertGreaterThanOrEqual(1, count($docs), 'At least one log document should be written');
        $doc = $docs[0];

        $this->assertSame($message, $doc['message']);
        $this->assertArrayHasKey('level', $doc);
        $this->assertArrayHasKey('level_name', $doc);
        $this->assertSame('app', $doc['channel']);
    }

    public function test_mongodb_channel_document_has_06_db_004_fields(): void
    {
        $message = '06-DB-004 check ' . $this->testRunId;
        Log::warning($message, ['test_run_id' => $this->testRunId]);

        $docs = $this->fetchLogsByTestRunId();
        $this->assertGreaterThanOrEqual(1, count($docs));
        $doc = $docs[0];

        $this->assertArrayHasKey('schema_version', $doc);
        $this->assertSame(LogModel::SCHEMA_VERSION, $doc['schema_version']);
        $this->assertArrayHasKey('created_at', $doc);
        $this->assertArrayHasKey('updated_at', $doc);
        $this->assertNotNull($doc['created_at']);
        $this->assertNotNull($doc['updated_at']);
    }

    public function test_mongodb_channel_includes_context_in_document(): void
    {
        $message = 'context test ' . $this->testRunId;
        $context = ['user_id' => 99, 'test_run_id' => $this->testRunId];
        Log::info($message, $context);

        $docs = $this->fetchLogsByTestRunId();
        $this->assertGreaterThanOrEqual(1, count($docs));
        $doc = $docs[0];

        $this->assertArrayHasKey('context', $doc);
        $this->assertSame(99, $doc['context']['user_id'] ?? null);
    }

    public function test_mongodb_channel_different_levels_write_correct_level_value(): void
    {
        $message = 'level test ' . $this->testRunId;
        Log::error($message, ['test_run_id' => $this->testRunId]);

        $docs = $this->fetchLogsByTestRunId();
        $this->assertGreaterThanOrEqual(1, count($docs));
        $doc = $docs[0];

        $this->assertSame('ERROR', $doc['level_name']);
        $this->assertSame(400, $doc['level']); // Monolog Level::Error->value
    }
}
