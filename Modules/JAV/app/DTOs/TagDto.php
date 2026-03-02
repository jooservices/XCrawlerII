<?php

declare(strict_types=1);

namespace Modules\JAV\DTOs;

final readonly class TagDto
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {
    }
}
