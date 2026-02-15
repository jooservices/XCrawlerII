<?php

namespace Modules\JAV\Events\Domain;

use Illuminate\Support\CarbonImmutable;
use Modules\JAV\Models\Jav;

class MovieCreated extends DomainEvent
{
    public function __construct(
        public readonly Jav $movie,
        ?string $source = null,
        ?string $correlationId = null,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($source, $correlationId, $eventId, $occurredAt);
    }
}
