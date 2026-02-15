<?php

namespace Modules\JAV\Events\Domain;

use Illuminate\Support\CarbonImmutable;

class SyncRunStarted extends DomainEvent
{
    public function __construct(
        public readonly string $provider,
        public readonly string $runType,
        public readonly ?int $expectedItems = null,
        ?string $source = null,
        ?string $correlationId = null,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($source ?? $provider, $correlationId, $eventId, $occurredAt);
    }
}
