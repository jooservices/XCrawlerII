<?php

declare(strict_types=1);

namespace Modules\Core\Contracts\Events;

use Carbon\CarbonImmutable;

/**
 * Contract for audit-log records (changed fields + previous/new snapshots).
 */
interface EventLogInterface
{
    public function getEventId(): ?string;

    public function getEventName(): string;

    public function getOccurredAt(): CarbonImmutable;

    public function getEntityType(): string;

    public function getEntityId(): string;

    /** @return list<string> */
    public function getChangedFields(): array;

    /** @return array<string, mixed>|null */
    public function getPrevious(): ?array;

    /** @return array<string, mixed>|null */
    public function getNew(): ?array;

    public function getCorrelationId(): ?string;

    public function getActorType(): ?string;

    public function getActorId(): ?string;
}
