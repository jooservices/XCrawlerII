<?php

namespace Modules\JAV\Events\Domain;

use Illuminate\Support\CarbonImmutable;

class ActorDeleted extends DomainEvent
{
    public function __construct(
        public readonly int|string $actorId,
        public readonly array $actorAttributes = [],
        ?string $source = null,
        ?string $correlationId = null,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($source, $correlationId, $eventId, $occurredAt);
    }
}
