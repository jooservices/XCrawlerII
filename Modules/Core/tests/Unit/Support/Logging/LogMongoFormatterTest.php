<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Support\Logging;

use Modules\Core\Models\Log;
use Modules\Core\Support\Logging\LogMongoFormatter;
use Modules\Core\Tests\TestCase;
use Monolog\Logger;

final class LogMongoFormatterTest extends TestCase
{
    public function test_format_returns_same_as_log_from_monolog_record(): void
    {
        $logger = new Logger('test');
        $holder = new \stdClass;
        $holder->record = null;
        $logger->pushHandler(new class($holder) implements \Monolog\Handler\HandlerInterface
        {
            private \stdClass $holder;

            public function __construct(\stdClass $holder)
            {
                $this->holder = $holder;
            }

            public function isHandling(\Monolog\LogRecord $record): bool
            {
                return true;
            }

            public function handle(\Monolog\LogRecord $record): bool
            {
                $this->holder->record = $record;

                return false;
            }

            public function handleBatch(array $records): void {}

            public function close(): void {}
        });
        $logger->info('formatter test', ['k' => 'v']);

        $this->assertNotNull($holder->record);
        $captured = $holder->record;

        $formatter = new LogMongoFormatter;
        $formatted = $formatter->format($captured);
        $expected = Log::fromMonologRecord($captured);

        $this->assertEquals($expected, $formatted);
    }

    public function test_format_batch_returns_array_of_documents(): void
    {
        $logger = new Logger('test');
        $holder = new \stdClass;
        $holder->records = [];
        $logger->pushHandler(new class($holder) implements \Monolog\Handler\HandlerInterface
        {
            private \stdClass $holder;

            public function __construct(\stdClass $holder)
            {
                $this->holder = $holder;
            }

            public function isHandling(\Monolog\LogRecord $record): bool
            {
                return true;
            }

            public function handle(\Monolog\LogRecord $record): bool
            {
                $this->holder->records[] = $record;

                return false;
            }

            public function handleBatch(array $records): void {}

            public function close(): void {}
        });
        $logger->info('one');
        $logger->warning('two');

        $records = $holder->records;
        $this->assertCount(2, $records);

        $formatter = new LogMongoFormatter;
        $batch = $formatter->formatBatch($records);

        $this->assertCount(2, $batch);
        $this->assertSame('one', $batch[0]['message']);
        $this->assertSame('two', $batch[1]['message']);
    }
}
