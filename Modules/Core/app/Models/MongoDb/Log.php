<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Log\Events\MessageLogged;
use Modules\Core\Database\Factories\LogFactory;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Laravel\Eloquent\Model;
use Monolog\Level;
use Monolog\LogRecord;

/**
 * Application log document model for MongoDB collection "logs".
 * Document shape and 06-DB-004 metadata (schema_version, created_at, updated_at) live here as single source of truth.
 *
 * @property array<string, mixed> $attributes
 */
final class Log extends Model
{
    use HasFactory;

    public const COLLECTION = 'logs';

    public const SCHEMA_VERSION = 1;

    protected $connection = 'mongodb';

    protected $table = self::COLLECTION;

    public $timestamps = false;

    protected $guarded = [];

    /**
     * Build a MongoDB document from a Monolog log record (06-DB-004: includes created_at, updated_at, schema_version).
     *
     * @return array<string, mixed>
     */
    public static function fromMonologRecord(LogRecord $record): array
    {
        $datetime = $record->datetime;
        $ts = $datetime instanceof \DateTimeInterface
            ? new UTCDateTime($datetime)
            : new UTCDateTime;

        $level = $record->level;
        $levelValue = $level instanceof Level ? $level->value : (int) $level;
        $levelName = $level instanceof Level ? $level->getName() : (string) $level;

        return [
            'message' => $record->message,
            'level' => $levelValue,
            'level_name' => $levelName,
            'channel' => $record->channel,
            'context' => self::toMongoSafeArray($record->context),
            'extra' => self::toMongoSafeArray($record->extra),
            'datetime' => $ts,
            'schema_version' => self::SCHEMA_VERSION,
            'created_at' => $ts,
            'updated_at' => $ts,
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
        $levelName = strtoupper((string) $event->level);

        return [
            'message' => (string) $event->message,
            'level' => self::levelValueFromName($levelName),
            'level_name' => $levelName,
            'channel' => 'app',
            'context' => self::toMongoSafeArray($event->context),
            'extra' => [],
            'datetime' => $ts,
            'schema_version' => self::SCHEMA_VERSION,
            'created_at' => $ts,
            'updated_at' => $ts,
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
                'file' => $value->getFile().':'.$value->getLine(),
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
