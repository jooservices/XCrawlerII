<?php

declare(strict_types=1);

namespace Modules\Core\Models\MongoDb;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Database\Factories\EventLogFactory;
use Modules\Core\Models\MongoDb;

/**
 * Audit log document: event_id, event_name, occurred_at, entity_*, changed_fields, previous/new, actor.
 *
 * @property string|null $event_id
 * @property string $event_name
 * @property \Carbon\CarbonImmutable|\MongoDB\BSON\UTCDateTime $occurred_at
 * @property string $entity_type
 * @property string $entity_id
 * @property array<int, string> $changed_fields
 * @property array<string, mixed>|null $previous
 * @property array<string, mixed>|null $new
 * @property string|null $correlation_id
 * @property string|null $actor_type
 * @property string|null $actor_id
 */
final class EventLog extends MongoDb
{
    use HasFactory;

    public const string COLLECTION = 'event_logs';

    protected $table = self::COLLECTION;

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'event_name',
        'occurred_at',
        'entity_type',
        'entity_id',
        'changed_fields',
        'previous',
        'new',
        'correlation_id',
        'actor_type',
        'actor_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'occurred_at' => 'datetime',
        'changed_fields' => 'array',
        'previous' => 'array',
        'new' => 'array',
    ];

    protected static function newFactory(): EventLogFactory
    {
        return EventLogFactory::new();
    }
}
