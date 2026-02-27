<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Models;

use Modules\Core\Database\Factories\LogFactory;
use Modules\Core\Models\Log;
use Modules\Core\Tests\TestCase;
use MongoDB\BSON\UTCDateTime;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

final class LogTest extends TestCase
{
    private static function createRecord(
        string $message = 'test',
        Level $level = Level::Info,
        string $channel = 'test',
        array $context = [],
        array $extra = [],
        ?\DateTimeImmutable $datetime = null,
    ): LogRecord {
        $logger = new Logger($channel);
        $holder = new \stdClass;
        $holder->record = null;
        $logger->pushHandler(new class($holder) implements \Monolog\Handler\HandlerInterface
        {
            private \stdClass $holder;

            public function __construct(\stdClass $holder)
            {
                $this->holder = $holder;
            }

            public function isHandling(LogRecord $record): bool
            {
                return true;
            }

            public function handle(LogRecord $record): bool
            {
                $this->holder->record = $record;

                return false;
            }

            public function handleBatch(array $records): void {}

            public function close(): void {}
        });
        $logger->log($level, $message, $context);

        if ($holder->record === null) {
            throw new \RuntimeException('Handler did not capture record');
        }

        return $holder->record;
    }

    public function test_from_monolog_record_returns_array_with_required_fields(): void
    {
        $record = self::createRecord('hello world', Level::Warning, 'app', ['user_id' => 1]);

        $doc = Log::fromMonologRecord($record);

        $this->assertIsArray($doc);
        $this->assertSame('hello world', $doc['message']);
        $this->assertSame('app', $doc['channel']);
        $this->assertArrayHasKey('level', $doc);
        $this->assertArrayHasKey('level_name', $doc);
        $this->assertSame(Level::Warning->value, $doc['level']);
        $this->assertSame(strtoupper(Level::Warning->getName()), $doc['level_name']);
        $this->assertArrayHasKey('context', $doc);
        $this->assertSame(['user_id' => 1], $doc['context']);
        $this->assertArrayHasKey('extra', $doc);
        $this->assertInstanceOf(UTCDateTime::class, $doc['datetime']);
        $this->assertSame(Log::SCHEMA_VERSION, $doc['schema_version']);
        $this->assertInstanceOf(UTCDateTime::class, $doc['created_at']);
        $this->assertInstanceOf(UTCDateTime::class, $doc['updated_at']);
    }

    public function test_from_monolog_record_schema_version_is_constant(): void
    {
        $record = self::createRecord('msg');

        $doc = Log::fromMonologRecord($record);

        $this->assertSame(Log::SCHEMA_VERSION, $doc['schema_version']);
    }

    public function test_from_monolog_record_created_at_and_updated_at_are_same(): void
    {
        $record = self::createRecord('msg');

        $doc = Log::fromMonologRecord($record);

        $this->assertEquals($doc['datetime'], $doc['created_at']);
        $this->assertEquals($doc['datetime'], $doc['updated_at']);
    }

    public function test_from_monolog_record_nested_context_is_mongo_safe(): void
    {
        $record = self::createRecord('msg', Level::Info, 'ch', [
            'nested' => ['a' => 1, 'b' => ['c' => 2]],
        ]);

        $doc = Log::fromMonologRecord($record);

        $this->assertIsArray($doc['context']);
        $this->assertSame(['a' => 1, 'b' => ['c' => 2]], $doc['context']['nested']);
    }

    public function test_from_monolog_record_exception_in_context_is_formatted(): void
    {
        $e = new \RuntimeException('oops', 42);
        $record = self::createRecord('error', Level::Error, 'ch', ['exception' => $e]);

        $doc = Log::fromMonologRecord($record);

        $this->assertIsArray($doc['context']['exception']);
        $this->assertSame(\RuntimeException::class, $doc['context']['exception']['class']);
        $this->assertSame('oops', $doc['context']['exception']['message']);
        $this->assertSame(42, $doc['context']['exception']['code']);
    }

    public function test_factory_produces_document_with_required_keys(): void
    {
        $attrs = LogFactory::new()->make()->getAttributes();

        $this->assertArrayHasKey('message', $attrs);
        $this->assertArrayHasKey('level', $attrs);
        $this->assertArrayHasKey('level_name', $attrs);
        $this->assertArrayHasKey('channel', $attrs);
        $this->assertArrayHasKey('context', $attrs);
        $this->assertArrayHasKey('extra', $attrs);
        $this->assertArrayHasKey('datetime', $attrs);
        $this->assertArrayHasKey('schema_version', $attrs);
        $this->assertSame(Log::SCHEMA_VERSION, $attrs['schema_version']);
        $this->assertArrayHasKey('created_at', $attrs);
        $this->assertArrayHasKey('updated_at', $attrs);
        $this->assertInstanceOf(UTCDateTime::class, $attrs['datetime']);
    }
}
