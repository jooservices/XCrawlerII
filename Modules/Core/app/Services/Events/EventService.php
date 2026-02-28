<?php

declare(strict_types=1);

namespace Modules\Core\Services\Events;

use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use Modules\Core\Constants\Events\PayloadSanitizerKeys;
use Modules\Core\Contracts\Events\EventLogInterface;
use Modules\Core\Contracts\Events\EventSourcingInterface;
use Modules\Core\Dto\Events\ActorContext;
use Modules\Core\Repositories\EventLogRepository;
use Modules\Core\Repositories\EventStoreRepository;

final class EventService
{
    public function __construct(
        private EventStoreRepository $eventStoreRepository,
        private EventLogRepository $eventLogRepository,
    ) {}

    public function recordSourcing(EventSourcingInterface $event, ?ActorContext $actor = null): void
    {
        $eventId = $event->getEventId();
        if ($eventId === null || $eventId === '') {
            $eventId = (string) Str::ulid();
        }

        $occurredAt = $event->getOccurredAt();
        if (! $occurredAt instanceof CarbonImmutable) {
            $occurredAt = CarbonImmutable::parse($occurredAt);
        }

        $payload = $this->sanitizePayload($event->getPayload());

        $attributes = [
            'event_id' => $eventId,
            'event_name' => $event->getEventName(),
            'occurred_at' => $occurredAt,
            'aggregate_type' => (string) $event->getAggregateType(),
            'aggregate_id' => (string) $event->getAggregateId(),
            'aggregate_version' => $event->getAggregateVersion(),
            'payload' => $payload,
            'correlation_id' => $actor?->correlationId ?? $event->getCorrelationId(),
            'causation_id' => $event->getCausationId(),
            'actor_type' => $actor !== null ? $actor->actorType->value : $event->getActorType(),
            'actor_id' => $actor?->actorId ?? $event->getActorId(),
        ];

        $this->eventStoreRepository->create($attributes);
    }

    public function recordLog(EventLogInterface $event, ?ActorContext $actor = null): void
    {
        $eventId = $event->getEventId();
        if ($eventId === null || $eventId === '') {
            $eventId = (string) Str::ulid();
        }

        $occurredAt = $event->getOccurredAt();
        if (! $occurredAt instanceof CarbonImmutable) {
            $occurredAt = CarbonImmutable::parse($occurredAt);
        }

        $previous = $event->getPrevious();
        $new = $event->getNew();
        if ($previous !== null) {
            $previous = $this->sanitizePayload($previous);
        }
        if ($new !== null) {
            $new = $this->sanitizePayload($new);
        }

        $attributes = [
            'event_id' => $eventId,
            'event_name' => $event->getEventName(),
            'occurred_at' => $occurredAt,
            'entity_type' => (string) $event->getEntityType(),
            'entity_id' => (string) $event->getEntityId(),
            'changed_fields' => $event->getChangedFields(),
            'previous' => $previous,
            'new' => $new,
            'correlation_id' => $actor?->correlationId ?? $event->getCorrelationId(),
            'actor_type' => $actor !== null ? $actor->actorType->value : $event->getActorType(),
            'actor_id' => $actor?->actorId ?? $event->getActorId(),
        ];

        $this->eventLogRepository->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function sanitizePayload(array $payload): array
    {
        $out = [];

        foreach ($payload as $key => $value) {
            if (PayloadSanitizerKeys::shouldSanitize($key)) {
                $out[$key] = PayloadSanitizerKeys::REDACT_PLACEHOLDER;

                continue;
            }

            if (is_array($value)) {
                $out[$key] = $this->sanitizePayload($value);

                continue;
            }

            $out[$key] = $value;
        }

        return $out;
    }
}
