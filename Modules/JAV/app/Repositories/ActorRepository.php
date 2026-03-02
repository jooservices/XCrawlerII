<?php

declare(strict_types=1);

namespace Modules\JAV\Repositories;

use Modules\JAV\Models\Actor;

final class ActorRepository
{
    /** @param  array<string, mixed>  $values */
    public function upsertByName(string $name, array $values = []): Actor
    {
        return Actor::query()->updateOrCreate(
            ['name' => $name],
            array_merge($values, ['name' => $name])
        );
    }

    public function findByName(string $name): ?Actor
    {
        return Actor::query()->where('name', $name)->first();
    }
}
