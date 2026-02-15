<?php

namespace Modules\JAV\Events\Domain;

use Illuminate\Support\CarbonImmutable;

class ActorReceivedNewMovie extends DomainEvent
{
    public function __construct(
        public readonly int|string $actorId,
        public readonly int|string $movieId,
        public readonly ?CarbonImmutable $releasedAt = null,
        ?string $source = null,
        ?string $correlationId = null,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($source, $correlationId, $eventId, $occurredAt);
    }
}
