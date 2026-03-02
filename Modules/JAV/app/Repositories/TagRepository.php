<?php

declare(strict_types=1);

namespace Modules\JAV\Repositories;

use Modules\JAV\Models\Tag;

final class TagRepository
{
    /** @param  array<string, mixed>  $values */
    public function upsertByName(string $name, array $values = []): Tag
    {
        return Tag::query()->updateOrCreate(
            ['name' => $name],
            array_merge($values, ['name' => $name])
        );
    }

    public function findByName(string $name): ?Tag
    {
        return Tag::query()->where('name', $name)->first();
    }
}
