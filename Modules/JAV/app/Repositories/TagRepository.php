<?php

namespace Modules\JAV\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Modules\JAV\Models\Tag;

class TagRepository
{
    public function query(): Builder
    {
        return Tag::query();
    }

    /**
     * @return array<int, string>
     */
    public function suggestions(int $limit = 700): array
    {
        return $this->query()
            ->orderBy('name')
            ->limit($limit)
            ->pluck('name')
            ->map(static fn(string $name): string => trim($name))
            ->filter(static fn(string $name): bool => $name !== '')
            ->values()
            ->all();
    }
}
