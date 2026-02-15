<?php

namespace Modules\JAV\Events\Domain;

use Illuminate\Support\CarbonImmutable;

class MovieImportedFromSource extends DomainEvent
{
    public function __construct(
        public readonly int|string $movieId,
        public readonly string $provider,
        public readonly ?string $externalId = null,
        public readonly array $payload = [],
        ?string $source = null,
        ?string $correlationId = null,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($source ?? $provider, $correlationId, $eventId, $occurredAt);
    }
}
