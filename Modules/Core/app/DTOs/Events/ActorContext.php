<?php

declare(strict_types=1);

namespace Modules\Core\DTOs\Events;

use Modules\Core\Enums\Events\ActorType;

final readonly class ActorContext
{
    public function __construct(
        public ActorType $actorType,
        public ?string $actorId = null,
        public ?string $correlationId = null,
        public ?string $requestId = null,
        public ?string $ip = null,
        public ?string $userAgent = null,
    ) {
    }

    public static function system(?string $correlationId = null): self
    {
        return new self(ActorType::System, null, $correlationId);
    }

    public static function user(string $userId, ?string $correlationId = null): self
    {
        return new self(ActorType::User, $userId, $correlationId);
    }
}
