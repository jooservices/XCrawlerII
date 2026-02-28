<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Stubs\Events;

use Carbon\CarbonImmutable;
use Modules\Core\Contracts\Events\EventLogInterface;

final class StubEventLog implements EventLogInterface
{
    /** @param list<string> $changedFields */
    public function __construct(
        private ?string $eventId,
        private string $eventName,
        private CarbonImmutable $occurredAt,
        private string $entityType,
        private string $entityId,
        private array $changedFields,
        private ?array $previous,
        private ?array $new,
        private ?string $correlationId = null,
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

    public function getEntityType(): string
    {
        return $this->entityType;
    }

    public function getEntityId(): string
    {
        return $this->entityId;
    }

    public function getChangedFields(): array
    {
        return $this->changedFields;
    }

    public function getPrevious(): ?array
    {
        return $this->previous;
    }

    public function getNew(): ?array
    {
        return $this->new;
    }

    public function getCorrelationId(): ?string
    {
        return $this->correlationId;
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
