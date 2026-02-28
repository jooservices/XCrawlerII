<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Events;

use Carbon\CarbonImmutable;

/**
 * Contract for event-sourcing records. Implementations provide getters for persistence.
 */
interface EventSourcingInterface
{
    public function getEventId(): ?string;

    public function getEventName(): string;

    public function getOccurredAt(): CarbonImmutable;

    public function getAggregateType(): string;

    public function getAggregateId(): string;

    public function getAggregateVersion(): ?int;

    /** @return array<string, mixed> */
    public function getPayload(): array;

    public function getCorrelationId(): ?string;

    public function getCausationId(): ?string;

    public function getActorType(): ?string;

    public function getActorId(): ?string;
}
