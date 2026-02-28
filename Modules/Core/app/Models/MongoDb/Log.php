<?php

declare(strict_types=1);

namespace Modules\Core\Models\MongoDb;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Log\Events\MessageLogged;
use Modules\Core\Database\Factories\LogFactory;
use Modules\Core\Models\MongoDb;
use MongoDB\BSON\UTCDateTime;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Application log document model for MongoDB collection "logs".
 * Document shape and 06-DB-004 metadata (schema_version, created_at, updated_at) live here as single source of truth.
 *
 * @property array<string, mixed> $attributes
 */
final class Log extends MongoDb
{
    use HasFactory;

    public const string COLLECTION = 'logs';

    public const int SCHEMA_VERSION = 1;

    protected $table = self::COLLECTION;

    protected $fillable = [
        'message',
        'level',
        'level_name',
        'channel',
        'context',
        'extra',
        'datetime',
        'schema_version',
    ];

    /**
     * Build a MongoDB document from a Monolog log record (06-DB-004: includes created_at, updated_at, schema_version).
     *
     * @return array<string, mixed>
     */
    public static function fromMonologRecord(LogRecord $record): array
    {
        $ts = new UTCDateTime($record->datetime);
        $levelValue = $record->level->value;
        $levelName = $record->level->getName();

        return [
            'message' => $record->message,
            'level' => $levelValue,
            'level_name' => $levelName,
            'channel' => $record->channel,
            'context' => self::toMongoSafeArray($record->context),
            'extra' => self::toMongoSafeArray($record->extra),
            'datetime' => $ts,
            'schema_version' => self::SCHEMA_VERSION,
        ];
    }

    /**
     * Build a MongoDB document from Laravel's MessageLogged event.
     *
     * @return array<string, mixed>
     */
    public static function fromMessageLogged(MessageLogged $event): array
    {
        $ts = new UTCDateTime();
        $levelName = strtoupper($event->level);

        return [
            'message' => $event->message,
            'level' => self::levelValueFromName($levelName),
            'level_name' => $levelName,
            'channel' => 'app',
            'context' => self::toMongoSafeArray($event->context),
            'extra' => [],
            'datetime' => $ts,
            'schema_version' => self::SCHEMA_VERSION,
        ];
    }

    /**
     * Recursively convert to MongoDB-safe array (max depth 3 to avoid unbounded nesting).
     */
    private static function toMongoSafeArray(mixed $value, int $depth = 0): mixed
    {
        $maxDepth = 3;

        if ($depth >= $maxDepth) {
            return is_scalar($value) ? $value : '[max depth]';
        }

        if (is_array($value)) {
            $out = [];
            foreach ($value as $k => $v) {
                $out[$k] = self::toMongoSafeArray($v, $depth + 1);
            }

            return $out;
        }

        if ($value instanceof \DateTimeInterface) {
            return new UTCDateTime($value);
        }

        if ($value instanceof \Throwable) {
            return [
                'class' => $value::class,
                'message' => $value->getMessage(),
                'code' => $value->getCode(),
                'file' => $value->getFile() . ':' . $value->getLine(),
                'trace' => $value->getTraceAsString(),
            ];
        }

        if (is_object($value)) {
            return ['class' => $value::class] + self::toMongoSafeArray((array) $value, $depth + 1);
        }

        return $value;
    }

    private static function levelValueFromName(string $levelName): int
    {
        return match ($levelName) {
            'DEBUG' => Level::Debug->value,
            'INFO' => Level::Info->value,
            'NOTICE' => Level::Notice->value,
            'WARNING' => Level::Warning->value,
            'ERROR' => Level::Error->value,
            'CRITICAL' => Level::Critical->value,
            'ALERT' => Level::Alert->value,
            'EMERGENCY' => Level::Emergency->value,
            default => Level::Info->value,
        };
    }

    protected static function newFactory(): LogFactory
    {
        return LogFactory::new();
    }
}
