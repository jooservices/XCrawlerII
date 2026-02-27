<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Models;

use DateTimeImmutable;
use Modules\Core\Database\Factories\LogFactory;
use Modules\Core\Models\MongoDb\Log;
use Modules\Core\Tests\TestCase;
use MongoDB\BSON\UTCDateTime;
use Monolog\Level;
use Monolog\LogRecord;
use RuntimeException;

final class LogTest extends TestCase
{
    private static function createRecord(
        string $message = 'test',
        Level $level = Level::Info,
        string $channel = 'test',
        array $context = [],
        array $extra = [],
    ): LogRecord {
        return new LogRecord(
            datetime: new DateTimeImmutable,
            channel: $channel,
            level: $level,
            message: $message,
            context: $context,
            extra: $extra,
        );
    }

    public function test_from_monolog_record_returns_array_with_required_fields(): void
    {
        $record = self::createRecord('hello world', Level::Warning, 'app', ['user_id' => 1]);

        $doc = Log::fromMonologRecord($record);

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
    }

    public function test_from_monolog_record_schema_version_is_constant(): void
    {
        $record = self::createRecord('msg');

        $doc = Log::fromMonologRecord($record);

        $this->assertSame(Log::SCHEMA_VERSION, $doc['schema_version']);
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
        $e = new RuntimeException('oops', 42);
        $record = self::createRecord('error', Level::Error, 'ch', ['exception' => $e]);

        $doc = Log::fromMonologRecord($record);

        $this->assertIsArray($doc['context']['exception']);
        $this->assertSame(RuntimeException::class, $doc['context']['exception']['class']);
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
