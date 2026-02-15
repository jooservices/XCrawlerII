<?php

namespace Modules\JAV\Events\Domain;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\CarbonImmutable;
use Illuminate\Support\Str;

abstract class DomainEvent implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public readonly string $eventId;

    public readonly CarbonImmutable $occurredAt;

    public readonly ?string $source;

    public readonly ?string $correlationId;

    public function __construct(
        ?string $source = null,
        ?string $correlationId = null,
        ?string $eventId = null,
        ?CarbonImmutable $occurredAt = null,
    ) {
        $this->source = $source;
        $this->correlationId = $correlationId;
        $this->eventId = $eventId ?? (string) Str::uuid();
        $this->occurredAt = $occurredAt ?? now()->toImmutable();
    }
}
