<?php

namespace Modules\Core\Dto\Interfaces;

interface IDto
{
    public function transform(mixed $response): ?static;

    public function toArray(): array;
}
