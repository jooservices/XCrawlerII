<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Stubs\Events;

use Carbon\CarbonImmutable;
use Modules\Core\Contracts\Events\EventSourcingInterface;

final class StubEventSourcing implements EventSourcingInterface
{
    public function __construct(
        private ?string $eventId,
        private string $eventName,
        private CarbonImmutable $occurredAt,
        private string $aggregateType,
        private string $aggregateId,
        private ?int $aggregateVersion,
        private array $payload,
        private ?string $correlationId = null,
        private ?string $causationId = null,
        private ?string $actorType = null,
        private ?string $actorId = null,
    ) {
    }

    public function getEventId(): ?string
    {
        return $this->eventId;
    }

    public function getEventName(): string
    {
        return $this->eventName;
    }

    public function getOccurredAt(): CarbonImmutable
    {
        return $this->occurredAt;
    }

    public function getAggregateType(): string
    {
        return $this->aggregateType;
    }

    public function getAggregateId(): string
    {
        return $this->aggregateId;
    }

    public function getAggregateVersion(): ?int
    {
        return $this->aggregateVersion;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
    }

    public function getCausationId(): ?string
    {
        return $this->causationId;
    }

    public function getActorType(): ?string
    {
        return $this->actorType;
    }

    public function getActorId(): ?string
    {
        return $this->actorId;
    }
}
