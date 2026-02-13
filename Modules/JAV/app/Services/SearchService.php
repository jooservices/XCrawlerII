<?php

namespace Modules\JAV\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class SearchService
{
    public function searchJav(string $query = '', array $filters = [], int $perPage = 30): LengthAwarePaginator
    {
        $search = Jav::search($query)->query(fn($q) => $q->with(['actors', 'tags']));

        if (!empty($filters['actor'])) {
            $search->where('actors', $filters['actor']);
        }

        if (!empty($filters['tag'])) {
            $search->where('tags', $filters['tag']);
        }

        return $search->paginate($perPage);
    }

    public function searchActors(string $query = '', int $perPage = 60): LengthAwarePaginator
    {
        return Actor::search($query)->paginate($perPage);
    }

    public function searchTags(string $query = '', int $perPage = 60): LengthAwarePaginator
    {
        return Tag::search($query)->paginate($perPage);
    }
}
