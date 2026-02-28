<?php

declare(strict_types=1);

namespace Modules\Core\Models\MongoDb;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Database\Factories\EventStoreFactory;
use Modules\Core\Models\MongoDb;

/**
 * Event-sourcing document: event_id, event_name, occurred_at, aggregate_*, payload, correlation/causation/actor.
 *
 * @property string|null $event_id
 * @property string $event_name
 * @property \Carbon\CarbonImmutable|\MongoDB\BSON\UTCDateTime $occurred_at
 * @property string $aggregate_type
 * @property string $aggregate_id
 * @property int|null $aggregate_version
 * @property array<string, mixed> $payload
 * @property string|null $correlation_id
 * @property string|null $causation_id
 * @property string|null $actor_type
 * @property string|null $actor_id
 */
final class EventStore extends MongoDb
{
    use HasFactory;

    public const string COLLECTION = 'event_stores';

    protected $table = self::COLLECTION;

    /** @var list<string> */
    protected $fillable = [
        'event_id',
        'event_name',
        'occurred_at',
        'aggregate_type',
        'aggregate_id',
        'aggregate_version',
        'payload',
        'correlation_id',
        'causation_id',
        'actor_type',
        'actor_id',
    ];

    /** @var array<string, string> */
    protected $casts = [
        'occurred_at' => 'datetime',
        'aggregate_version' => 'integer',
        'payload' => 'array',
    ];

    protected static function newFactory(): EventStoreFactory
    {
        return EventStoreFactory::new();
    }
}
