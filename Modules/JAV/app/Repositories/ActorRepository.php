<?php

namespace Modules\JAV\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\JAV\Models\Actor;

class ActorRepository
{
    public function query(): Builder
    {
        return Actor::query();
    }

    /**
     * @return array<int, string>
     */
    public function suggestions(int $limit = 500): array
    {
        return $this->query()
            ->orderBy('name')
            ->limit($limit)
            ->pluck('name')
            ->map(static fn (string $name): string => trim($name))
            ->filter(static fn (string $name): bool => $name !== '')
            ->values()
            ->all();
    }

    public function actorMovies(Actor $actor, int $perPage = 30): LengthAwarePaginator
    {
        return $actor->javs()
            ->with(['actors', 'tags'])
            ->orderByDesc('date')
            ->paginate($perPage);
    }

    /**
     * @return array<int, string>
     */
    public function uniqueColumnValues(string $column, int $limit = 200): array
    {
        return $this->query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->limit($limit)
            ->pluck($column)
            ->map(static fn (mixed $value): string => trim((string) $value))
            ->filter(static fn (string $value): bool => $value !== '')
            ->values()
            ->all();
    }
}
