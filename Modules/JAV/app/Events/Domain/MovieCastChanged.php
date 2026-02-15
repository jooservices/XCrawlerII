<?php

namespace Modules\JAV\Events\Domain;

use Illuminate\Support\CarbonImmutable;

class MovieCastChanged extends DomainEvent
{
    public function __construct(
        public readonly int|string $movieId,
        public readonly array $addedActorIds = [],
        public readonly array $removedActorIds = [],
        ?string $source = null,
        ?string $correlationId = null,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($source, $correlationId, $eventId, $occurredAt);
    }
}
