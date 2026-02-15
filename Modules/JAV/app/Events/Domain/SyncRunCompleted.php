<?php

namespace Modules\JAV\Events\Domain;

use Illuminate\Support\CarbonImmutable;

class SyncRunCompleted extends DomainEvent
{
    public function __construct(
        public readonly string $provider,
        public readonly string $runType,
        public readonly int $processedItems,
        public readonly int $createdItems = 0,
        public readonly int $updatedItems = 0,
        public readonly int $failedItems = 0,
        ?string $source = null,
        ?string $correlationId = null,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        parent::__construct($source ?? $provider, $correlationId, $eventId, $occurredAt);
    }
}
