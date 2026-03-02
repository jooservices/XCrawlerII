<?php

declare(strict_types=1);

namespace Modules\JAV\DTOs;

final readonly class ActorDto
{
    public function __construct(
        public string $name,
        public ?string $avatar = null,
        public ?array $aliases = null,
        public ?\DateTimeInterface $birthDate = null,
        public ?string $birthplace = null,
        public ?string $bloodType = null,
        public ?int $height = null,
        public ?int $weight = null,
        public ?int $bust = null,
        public ?int $waist = null,
        public ?int $hip = null,
        public ?string $cupSize = null,
        public ?array $hobbies = null,
        public ?array $skills = null,
        public ?array $attributes = null,
        public ?\DateTimeInterface $crawledAt = null,
        public ?\DateTimeInterface $seenAt = null,
    ) {
    }
}
