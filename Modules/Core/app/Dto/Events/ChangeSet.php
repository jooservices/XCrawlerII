<?php

declare(strict_types=1);

namespace Modules\Core\Dto\Events;

final readonly class ChangeSet
{
    /** @param list<string> $changedFields */
    public function __construct(
        public array $changedFields,
        /** @var array<string, mixed>|null */
        public ?array $previousPartial,
        /** @var array<string, mixed>|null */
        public ?array $newPartial,
    ) {}

    public function hasChanges(): bool
    {
        return $this->changedFields !== [];
    }
}
