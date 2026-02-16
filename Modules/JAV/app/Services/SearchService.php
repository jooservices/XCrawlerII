<?php

namespace Modules\JAV\Services;

use Carbon\Carbon;
use Elastic\Elasticsearch\Client;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Facades\Cache;
use Modules\JAV\Events\RelatedCacheHit;
use Modules\JAV\Events\RelatedCacheMiss;
use Modules\JAV\Events\RelatedCacheWarmed;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Tag;

class SearchService // NOSONAR
{
    use \Modules\JAV\Traits\ElasticsearchHelpers;

    public function searchJav(string $query = '', array $filters = [], int $perPage = 30, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $filters = $this->normalizeFilters($filters);
        $sort = $this->normalizeSort($sort);
        $direction = $this->normalizeDirection($direction);

        if ($this->isElasticsearchAvailable('jav')) {
            try {
                return $this->searchJavViaElasticsearch($query, $filters, $perPage, $sort, $direction);
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::warning('Elasticsearch movie search failed, falling back to database search.', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }
        \Illuminate\Support\Facades\Log::warning('Elasticsearch unavailable, using database search.');

        $builder = Jav::query()->with(['actors', 'tags']);
        $this->applyDatabaseFilters($builder, $query, $filters);

        if ($sort !== null) {
            $builder->orderBy($sort, $direction);
        } else {
            $builder->orderByDesc('date');
        }

        return $builder->paginate($perPage);
    }

    public function searchActors(
        string $query = '',
        array $filters = [],
        int $perPage = 60,
        ?string $sort = null,
        string $direction = 'desc'
    ): LengthAwarePaginator {
        $filters = $this->normalizeFilters($filters);
        $sort = $this->normalizeActorSort($sort);
        $direction = $this->normalizeDirection($direction);

        if ($this->isElasticsearchAvailable('actors')) {
            try {
                return $this->searchActorsViaElasticsearch($query, $filters, $perPage, $sort, $direction);
            } catch (\Throwable $exception) {
                \Illuminate\Support\Facades\Log::warning('Elasticsearch actor search failed, falling back to database.', [
                    'error' => $exception->getMessage(),
                ]);
            }
        }

        $builder = Actor::query()
            ->withCount(['javs', 'favorites'])
            ->withSum('javs as jav_views', 'views');

        if ($query !== '') {
            $builder->where('name', 'like', "%{$query}%");
        }

        $this->applyActorTagsFilter($builder, $filters);
        if (($filters['age'] ?? null) !== null || ($filters['age_min'] ?? null) !== null || ($filters['age_max'] ?? null) !== null) {
            $this->applyActorAgeFilter($builder, $filters);
        }
        $this->applyActorBioFilters($builder, $filters);

        if ($sort !== null) {
            $builder->orderBy($sort, $direction);
        } else {
            $builder->orderByDesc('javs_count');
        }

        return $builder->paginate($perPage)->withQueryString();
    }

    public function searchTags(string $query = '', int $perPage = 60, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $allowedSorts = ['javs_count', 'name', 'created_at'];
        $sort = in_array((string) $sort, $allowedSorts, true) ? (string) $sort : 'javs_count';
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        $builder = Tag::query()->withCount('javs');

        if (! empty($query)) {
            $builder->where('name', 'like', "%{$query}%");
        }

        return $builder
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Get related movies by actors
     */
    /**
     * Get related movies by actors
     */
    public function getRelatedByActors(Jav $jav, int $limit = 10): \Illuminate\Support\Collection
    {
        $actorNames = $jav->actors->pluck('name')->toArray();
        if (empty($actorNames)) {
            return collect();
        }
        $cacheKey = 'jav:related:actors:'.$jav->id.':v1';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $collection = $cached instanceof \Illuminate\Support\Collection ? $cached : collect($cached);
            RelatedCacheHit::dispatch('jav', 'actors', (int) $jav->id, $limit, $cacheKey, $collection->count());

            return $collection;
        }

        RelatedCacheMiss::dispatch('jav', 'actors', (int) $jav->id, $limit, $cacheKey);

        $results = (function () use ($jav, $actorNames, $limit) {
            if ($this->isElasticsearchAvailable('jav')) {
                // Search for movies with any of the same actors, excluding the current movie
                return Jav::search('*')
                    ->query(fn ($q) => $q
                        ->with(['actors', 'tags'])
                        ->where('id', '!=', $jav->id)
                    )
                    ->take($limit + 20)
                    ->get()
                    ->filter(function ($item) use ($actorNames) {
                        $itemActorsRaw = is_array($item->actors)
                            ? collect($item->actors)
                            : collect($item->actors ?? []);
                        $itemActors = $itemActorsRaw
                            ->map(static function ($actor): string {
                                if (is_array($actor)) {
                                    return trim((string) ($actor['name'] ?? ''));
                                }
                                if (is_object($actor)) {
                                    return trim((string) ($actor->name ?? ''));
                                }

                                return trim((string) $actor);
                            })
                            ->filter(static fn (string $name): bool => $name !== '')
                            ->values()
                            ->toArray();

                        return ! empty(array_intersect($itemActors, $actorNames));
                    })
                    ->take($limit)
                    ->values();
            }
            \Illuminate\Support\Facades\Log::warning('Elasticsearch unavailable (related actors), using database.');

            return Jav::query()
                ->with(['actors', 'tags'])
                ->whereHas('actors', function ($q) use ($actorNames) {
                    $q->whereIn('name', $actorNames);
                })
                ->where('id', '!=', $jav->id)
                ->orderByDesc('date')
                ->take($limit)
                ->get();
        })();

        Cache::put($cacheKey, $results, now()->addMinutes(10));
        RelatedCacheWarmed::dispatch('jav', 'actors', (int) $jav->id, $limit, $cacheKey, $results->count(), 600);

        return $results;
    }

    /**
     * Get related movies by tags
     */
    /**
     * Get related movies by tags
     */
    public function getRelatedByTags(Jav $jav, int $limit = 10): \Illuminate\Support\Collection
    {
        $tagNames = $jav->tags->pluck('name')->toArray();
        if (empty($tagNames)) {
            return collect();
        }
        $cacheKey = 'jav:related:tags:'.$jav->id.':v1';
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            $collection = $cached instanceof \Illuminate\Support\Collection ? $cached : collect($cached);
            RelatedCacheHit::dispatch('jav', 'tags', (int) $jav->id, $limit, $cacheKey, $collection->count());

            return $collection;
        }

        RelatedCacheMiss::dispatch('jav', 'tags', (int) $jav->id, $limit, $cacheKey);

        $results = (function () use ($jav, $tagNames, $limit) {
            if ($this->isElasticsearchAvailable('jav')) {
                // Search for movies with any of the same tags, excluding the current movie
                return Jav::search('*')
                    ->query(fn ($q) => $q
                        ->with(['actors', 'tags'])
                        ->where('id', '!=', $jav->id)
                    )
                    ->take($limit + 20)
                    ->get()
                    ->filter(function ($item) use ($tagNames) {
                        $itemTagsRaw = is_array($item->tags)
                            ? collect($item->tags)
                            : collect($item->tags ?? []);
                        $itemTags = $itemTagsRaw
                            ->map(static function ($tag): string {
                                if (is_array($tag)) {
                                    return trim((string) ($tag['name'] ?? ''));
                                }
                                if (is_object($tag)) {
                                    return trim((string) ($tag->name ?? ''));
                                }

                                return trim((string) $tag);
                            })
                            ->filter(static fn (string $name): bool => $name !== '')
                            ->values()
                            ->toArray();

                        return ! empty(array_intersect($itemTags, $tagNames));
                    })
                    ->take($limit)
                    ->values();
            }
            \Illuminate\Support\Facades\Log::warning('Elasticsearch unavailable (related tags), using database.');

            return Jav::query()
                ->with(['actors', 'tags'])
                ->whereHas('tags', function ($q) use ($tagNames) {
                    $q->whereIn('name', $tagNames);
                })
                ->where('id', '!=', $jav->id)
                ->orderByDesc('date')
                ->take($limit)
                ->get();
        })();

        Cache::put($cacheKey, $results, now()->addMinutes(10));
        RelatedCacheWarmed::dispatch('jav', 'tags', (int) $jav->id, $limit, $cacheKey, $results->count(), 600);

        return $results;
    }

    public function applyDatabaseFilters(Builder $builder, string $query, array $filters): void
    {
        $filters = $this->normalizeFilters($filters);

        if ($query !== '') {
            $builder->where(function (Builder $q) use ($query): void {
                $q->where('title', 'like', "%{$query}%")
                    ->orWhere('code', 'like', "%{$query}%")
                    ->orWhere('uuid', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });
        }

        $this->applyTagFilters($builder, $filters);
        $this->applyActorFilters($builder, $filters);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeFilters(array $filters): array // NOSONAR
    {
        $tagsFromArray = [];
        if (isset($filters['tags']) && is_array($filters['tags'])) {
            $tagsFromArray = collect($filters['tags'])
                ->map(static fn (mixed $tag): string => trim((string) $tag))
                ->filter(static fn (string $tag): bool => $tag !== '')
                ->values()
                ->all();
        }

        $tags = array_values(array_unique(array_merge(
            $tagsFromArray,
            $this->parseCsv($filters['tag'] ?? null)
        )));

        $age = isset($filters['age']) && $filters['age'] !== '' ? (int) $filters['age'] : null;
        $ageMin = isset($filters['age_min']) && $filters['age_min'] !== '' ? (int) $filters['age_min'] : null;
        $ageMax = isset($filters['age_max']) && $filters['age_max'] !== '' ? (int) $filters['age_max'] : null;

        if ($age !== null) {
            $ageMin = null;
            $ageMax = null;
        } elseif ($ageMin !== null && $ageMax !== null && $ageMin > $ageMax) {
            [$ageMin, $ageMax] = [$ageMax, $ageMin];
        }

        $tag = count($tags) === 1 ? $tags[0] : trim((string) ($filters['tag'] ?? ''));
        $actor = trim((string) ($filters['actor'] ?? ''));
        $bioKey = trim((string) ($filters['bio_key'] ?? ''));
        $bioValue = trim((string) ($filters['bio_value'] ?? ''));
        $bioFilters = $this->normalizeBioFilters(
            isset($filters['bio_filters']) && is_array($filters['bio_filters']) ? $filters['bio_filters'] : [],
            $bioKey !== '' ? $bioKey : null,
            $bioValue !== '' ? $bioValue : null
        );

        return [
            'actor' => $actor !== '' ? $actor : null,
            'actors' => $this->parseCsv($filters['actor'] ?? null),
            'tag' => $tag !== '' ? $tag : null,
            'tags' => $tags,
            'tags_mode' => ($filters['tags_mode'] ?? 'any') === 'all' ? 'all' : 'any',
            'age' => $age,
            'age_min' => $ageMin,
            'age_max' => $ageMax,
            'bio_key' => $bioFilters[0]['key'] ?? null,
            'bio_value' => $bioFilters[0]['value'] ?? null,
            'bio_filters' => $bioFilters,
        ];
    }

    private function searchJavViaElasticsearch(// NOSONAR
        string $query,
        array $filters,
        int $perPage,
        ?string $sort,
        string $direction
    ): LengthAwarePaginator {
        /** @var Client $client */
        $client = app(Client::class);
        $page = Paginator::resolveCurrentPage();
        $from = max(0, ($page - 1) * $perPage);
        $must = [];
        $filterClauses = [];

        if ($query !== '') {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['title^3', 'code^4', 'uuid', 'description'],
                ],
            ];
        } else {
            $must[] = ['match_all' => (object) []];
        }

        $tags = $filters['tags'] ?? [];
        if (! empty($tags)) {
            $normalizedTags = array_values(array_unique(array_map(
                static fn (string $tag): string => mb_strtolower(trim($tag)),
                $tags
            )));

            if (($filters['tags_mode'] ?? 'any') === 'all') {
                foreach ($normalizedTags as $tagName) {
                    $filterClauses[] = ['term' => ['tags_keyword.keyword' => $tagName]];
                }
            } else {
                $filterClauses[] = ['terms' => ['tags_keyword.keyword' => $normalizedTags]];
            }
        }

        $actors = $filters['actors'] ?? [];
        if (! empty($actors)) {
            foreach ($actors as $actorName) {
                $normalizedActor = mb_strtolower(trim((string) $actorName));
                if ($normalizedActor === '') {
                    continue;
                }
                $filterClauses[] = ['wildcard' => ['actor_names_keyword.keyword' => "*{$normalizedActor}*"]];
            }
        }

        $age = $filters['age'] ?? null;
        $ageMin = $filters['age_min'] ?? null;
        $ageMax = $filters['age_max'] ?? null;
        if ($age !== null) {
            $filterClauses[] = ['term' => ['actor_ages' => (int) $age]];
        } else {
            if ($ageMin !== null) {
                $filterClauses[] = ['range' => ['actor_ages' => ['gte' => (int) $ageMin]]];
            }
            if ($ageMax !== null) {
                $filterClauses[] = ['range' => ['actor_ages' => ['lte' => (int) $ageMax]]];
            }
        }

        $bioFilters = $filters['bio_filters'] ?? [];
        foreach ($bioFilters as $bioFilter) {
            $bioKey = strtolower(trim((string) ($bioFilter['key'] ?? '')));
            $bioValue = mb_strtolower(trim((string) ($bioFilter['value'] ?? '')));

            if ($bioKey !== '' && $bioValue !== '') {
                $filterClauses[] = ['wildcard' => ['actor_profile_pairs.keyword' => "{$bioKey}:*{$bioValue}*"]];

                continue;
            }

            if ($bioKey !== '') {
                $filterClauses[] = ['term' => ['actor_profile_keys.keyword' => $bioKey]];
            }

            if ($bioValue !== '') {
                $must[] = ['match_phrase' => ['actor_profile_text' => $bioValue]];
            }
        }

        $sortField = $sort ?? 'date';
        $response = $client->search([
            'index' => $this->indexNameFor(Jav::class),
            'body' => [
                'from' => $from,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'filter' => $filterClauses,
                    ],
                ],
                'sort' => [
                    [$sortField => ['order' => $direction]],
                    ['id' => ['order' => 'asc']],
                ],
            ],
        ])->asArray();

        $hits = $response['hits']['hits'] ?? [];
        $ids = array_values(array_map(static fn (array $hit): int => (int) ($hit['_id'] ?? 0), $hits));
        $total = (int) ($response['hits']['total']['value'] ?? 0);

        return $this->hydrateJavPaginatorFromIds($ids, $total, $perPage, $page);
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function hydrateJavPaginatorFromIds(array $ids, int $total, int $perPage, int $page): LengthAwarePaginator
    {
        if (empty($ids)) {
            return new Paginator(collect(), $total, $perPage, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]);
        }

        $orderedIds = implode(',', array_map('intval', $ids));
        $items = Jav::query()
            ->with(['actors', 'tags'])
            ->whereIn('id', $ids)
            ->orderByRaw("FIELD(id, {$orderedIds})")
            ->get();

        return new Paginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }

    private function applyTagFilters(Builder $builder, array $filters): void
    {
        $tags = $filters['tags'] ?? [];
        if (empty($tags)) {
            return;
        }

        if (($filters['tags_mode'] ?? 'any') === 'all') {
            foreach ($tags as $tagName) {
                $builder->whereHas('tags', function (Builder $tagQuery) use ($tagName): void {
                    $tagQuery->where('name', $tagName);
                });
            }

            return;
        }

        $builder->whereHas('tags', function (Builder $tagQuery) use ($tags): void {
            $tagQuery->whereIn('name', $tags);
        });
    }

    private function applyActorFilters(Builder $builder, array $filters): void
    {
        $hasActorFilter = ! empty($filters['actors']);
        $hasAgeFilter = ($filters['age'] ?? null) !== null
            || ($filters['age_min'] ?? null) !== null
            || ($filters['age_max'] ?? null) !== null;
        $hasBioFilter = ! empty($filters['bio_filters']);

        if (! $hasActorFilter && ! $hasAgeFilter && ! $hasBioFilter) {
            return;
        }

        $builder->whereHas('actors', function (Builder $actorQuery) use ($filters, $hasActorFilter, $hasAgeFilter, $hasBioFilter): void {
            if ($hasActorFilter) {
                $actorNames = $filters['actors'];
                $actorQuery->where(function (Builder $nameQuery) use ($actorNames): void {
                    foreach ($actorNames as $name) {
                        $nameQuery->orWhere('name', 'like', "%{$name}%");
                    }
                });
            }

            if ($hasAgeFilter) {
                $this->applyActorAgeFilter($actorQuery, $filters);
            }

            if ($hasBioFilter) {
                $this->applyActorBioFilters($actorQuery, $filters);
            }
        });
    }

    private function applyActorAgeFilter(Builder $actorQuery, array $filters): void
    {
        $today = Carbon::today();

        $actorQuery->where(function (Builder $ageQuery) use ($filters, $today): void {
            $ageQuery->where(function (Builder $profileBirthQuery) use ($filters, $today): void {
                $profileBirthQuery->whereHas('profileAttributes', function (Builder $attributeQuery) use ($filters, $today): void {
                    $attributeQuery->where('kind', 'birth_date');
                    $this->applyBirthDateConstraint($attributeQuery, 'value_date', $filters, $today);
                });
            })->orWhere(function (Builder $legacyBirthQuery) use ($filters, $today): void {
                $this->applyBirthDateConstraint($legacyBirthQuery, 'xcity_birth_date', $filters, $today);
            });
        });
    }

    private function applyBirthDateConstraint(Builder $query, string $column, array $filters, Carbon $today): void
    {
        if (($filters['age'] ?? null) !== null) {
            $age = (int) $filters['age'];
            $maxBirthDate = $today->copy()->subYears($age)->toDateString();
            $minBirthDate = $today->copy()->subYears($age + 1)->addDay()->toDateString();
            $query->whereDate($column, '>=', $minBirthDate)
                ->whereDate($column, '<=', $maxBirthDate);

            return;
        }

        $ageMin = $filters['age_min'] ?? null;
        $ageMax = $filters['age_max'] ?? null;

        if ($ageMin !== null) {
            $query->whereDate($column, '<=', $today->copy()->subYears((int) $ageMin)->toDateString());
        }

        if ($ageMax !== null) {
            $query->whereDate($column, '>=', $today->copy()->subYears(((int) $ageMax) + 1)->addDay()->toDateString());
        }
    }

    private function applyActorBioFilters(Builder $actorQuery, array $filters): void // NOSONAR
    {
        $bioFilters = isset($filters['bio_filters']) && is_array($filters['bio_filters']) ? $filters['bio_filters'] : [];

        foreach ($bioFilters as $bioFilter) {
            $bioKey = isset($bioFilter['key']) ? trim((string) $bioFilter['key']) : '';
            $bioValue = isset($bioFilter['value']) ? trim((string) $bioFilter['value']) : '';

            if ($bioKey === '' && $bioValue === '') {
                continue;
            }

            $normalizedKey = $bioKey !== '' ? strtolower(str_replace(' ', '_', $bioKey)) : null;
            $value = $bioValue !== '' ? $bioValue : null;
            $legacyColumn = $this->mapBioKeyToLegacyActorColumn($normalizedKey);

            $actorQuery->where(function (Builder $bioQuery) use ($normalizedKey, $value, $legacyColumn): void {
                $bioQuery->whereHas('profileAttributes', function (Builder $attributeQuery) use ($normalizedKey, $value): void {
                    if ($normalizedKey !== null) {
                        $attributeQuery->where(function (Builder $kindQuery) use ($normalizedKey): void {
                            $kindQuery->where('kind', $normalizedKey)
                                ->orWhere('value_label', 'like', '%'.str_replace('_', ' ', $normalizedKey).'%');
                        });
                    }

                    if ($value !== null) {
                        $this->applyAttributeValueLikeFilter($attributeQuery, $value);
                    }
                });

                if ($legacyColumn !== null) {
                    if ($value !== null) {
                        $bioQuery->orWhere($legacyColumn, 'like', "%{$value}%");
                    } else {
                        $bioQuery->orWhereNotNull($legacyColumn)
                            ->where($legacyColumn, '!=', '');
                    }

                    return;
                }

                if ($value !== null) {
                    $bioQuery->orWhere(function (Builder $legacyAnyQuery) use ($value): void {
                        $legacyAnyQuery
                            ->where('xcity_blood_type', 'like', "%{$value}%")
                            ->orWhere('xcity_city_of_birth', 'like', "%{$value}%")
                            ->orWhere('xcity_height', 'like', "%{$value}%")
                            ->orWhere('xcity_size', 'like', "%{$value}%")
                            ->orWhere('xcity_hobby', 'like', "%{$value}%")
                            ->orWhere('xcity_special_skill', 'like', "%{$value}%")
                            ->orWhere('xcity_other', 'like', "%{$value}%");
                    });
                }
            });
        }
    }

    private function applyAttributeValueLikeFilter(Builder $attributeQuery, string $bioValue): void
    {
        $like = "%{$bioValue}%";

        $attributeQuery->where(function (Builder $valueQuery) use ($like): void {
            $valueQuery->where('value_string', 'like', $like)
                ->orWhere('raw_value', 'like', $like)
                ->orWhere('value_label', 'like', $like)
                ->orWhereRaw('CAST(value_number AS CHAR) LIKE ?', [$like])
                ->orWhereRaw('CAST(value_date AS CHAR) LIKE ?', [$like]);
        });
    }

    private function mapBioKeyToLegacyActorColumn(?string $bioKey): ?string
    {
        if ($bioKey === null) {
            return null;
        }

        return match ($bioKey) {
            'birth_date' => 'xcity_birth_date',
            'blood_type' => 'xcity_blood_type',
            'city_of_birth' => 'xcity_city_of_birth',
            'height' => 'xcity_height',
            'size' => 'xcity_size',
            'hobby' => 'xcity_hobby',
            'special_skill' => 'xcity_special_skill',
            'other' => 'xcity_other',
            default => null,
        };
    }

    /**
     * @return array<int, string>
     */
    private function parseCsv(mixed $value): array
    {
        $string = trim((string) ($value ?? ''));
        if ($string === '') {
            return [];
        }

        return collect(explode(',', $string))
            ->map(static fn (string $item): string => trim($item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $bioFilters
     * @return array<int, array{key: ?string, value: ?string}>
     */
    private function normalizeBioFilters(array $bioFilters, ?string $singleBioKey = null, ?string $singleBioValue = null): array // NOSONAR
    {
        $normalized = collect($bioFilters)
            ->map(function (mixed $bioFilter): array {
                if (! is_array($bioFilter)) {
                    return ['key' => null, 'value' => null];
                }

                $key = trim((string) ($bioFilter['key'] ?? ''));
                $value = trim((string) ($bioFilter['value'] ?? ''));

                return [
                    'key' => $key !== '' ? strtolower(str_replace(' ', '_', $key)) : null,
                    'value' => $value !== '' ? $value : null,
                ];
            })
            ->filter(static fn (array $bioFilter): bool => $bioFilter['key'] !== null || $bioFilter['value'] !== null)
            ->values();

        if ($normalized->isEmpty()) {
            $key = trim((string) ($singleBioKey ?? ''));
            $value = trim((string) ($singleBioValue ?? ''));

            if ($key !== '' || $value !== '') {
                $normalized->push([
                    'key' => $key !== '' ? strtolower(str_replace(' ', '_', $key)) : null,
                    'value' => $value !== '' ? $value : null,
                ]);
            }
        }

        return $normalized->all();
    }

    private function normalizeSort(?string $sort): ?string
    {
        return in_array((string) $sort, ['created_at', 'updated_at', 'views', 'downloads'], true)
            ? (string) $sort
            : null;
    }

    private function normalizeActorSort(?string $sort): ?string
    {
        return in_array((string) $sort, ['javs_count', 'name', 'created_at', 'updated_at'], true)
            ? (string) $sort
            : null;
    }

    private function searchActorsViaElasticsearch(// NOSONAR
        string $query,
        array $filters,
        int $perPage,
        ?string $sort,
        string $direction
    ): LengthAwarePaginator {
        /** @var Client $client */
        $client = app(Client::class);
        $page = Paginator::resolveCurrentPage();
        $from = max(0, ($page - 1) * $perPage);
        $must = [];
        $filterClauses = [];

        if ($query !== '') {
            $must[] = [
                'multi_match' => [
                    'query' => $query,
                    'fields' => ['name^3', 'bio', 'bio_lower'],
                ],
            ];
        } else {
            $must[] = ['match_all' => (object) []];
        }

        $tags = $filters['tags'] ?? [];
        if (! empty($tags)) {
            $normalizedTags = array_values(array_unique(array_map(
                static fn (string $tag): string => mb_strtolower(trim($tag)),
                $tags
            )));

            if (($filters['tags_mode'] ?? 'any') === 'all') {
                foreach ($normalizedTags as $tagName) {
                    $filterClauses[] = ['term' => ['movie_tags_keyword.keyword' => $tagName]];
                }
            } else {
                $filterClauses[] = ['terms' => ['movie_tags_keyword.keyword' => $normalizedTags]];
            }
        }

        $age = $filters['age'] ?? null;
        $ageMin = $filters['age_min'] ?? null;
        $ageMax = $filters['age_max'] ?? null;
        $today = Carbon::today();
        if ($age !== null) {
            $age = (int) $age;
            $maxBirthDate = $today->copy()->subYears($age)->toDateString();
            $minBirthDate = $today->copy()->subYears($age + 1)->addDay()->toDateString();
            $filterClauses[] = [
                'range' => [
                    'birth_date' => [
                        'gte' => $minBirthDate,
                        'lte' => $maxBirthDate,
                    ],
                ],
            ];
        } else {
            if ($ageMin !== null) {
                $filterClauses[] = [
                    'range' => [
                        'birth_date' => [
                            'lte' => $today->copy()->subYears((int) $ageMin)->toDateString(),
                        ],
                    ],
                ];
            }
            if ($ageMax !== null) {
                $filterClauses[] = [
                    'range' => [
                        'birth_date' => [
                            'gte' => $today->copy()->subYears(((int) $ageMax) + 1)->addDay()->toDateString(),
                        ],
                    ],
                ];
            }
        }

        $bioFilters = $filters['bio_filters'] ?? [];
        foreach ($bioFilters as $bioFilter) {
            $bioKey = strtolower(trim((string) ($bioFilter['key'] ?? '')));
            $bioValue = mb_strtolower(trim((string) ($bioFilter['value'] ?? '')));

            if ($bioKey !== '' && $bioValue !== '') {
                $filterClauses[] = ['wildcard' => ['profile_attribute_pairs.keyword' => "{$bioKey}:*{$bioValue}*"]];

                continue;
            }

            if ($bioKey !== '') {
                $filterClauses[] = ['term' => ['profile_attribute_keys.keyword' => $bioKey]];
            }

            if ($bioValue !== '') {
                $must[] = ['match_phrase' => ['bio_lower' => $bioValue]];
            }
        }

        $sortField = $sort ?? 'javs_count';
        $response = $client->search([
            'index' => $this->indexNameFor(Actor::class),
            'body' => [
                'from' => $from,
                'size' => $perPage,
                'query' => [
                    'bool' => [
                        'must' => $must,
                        'filter' => $filterClauses,
                    ],
                ],
                'sort' => [
                    [$sortField => ['order' => $direction]],
                    ['id' => ['order' => 'asc']],
                ],
            ],
        ])->asArray();

        $hits = $response['hits']['hits'] ?? [];
        $ids = array_values(array_map(static fn (array $hit): int => (int) ($hit['_id'] ?? 0), $hits));
        $total = (int) ($response['hits']['total']['value'] ?? 0);

        return $this->hydrateActorPaginatorFromIds($ids, $total, $perPage, $page);
    }

    /**
     * @param  array<int, int>  $ids
     */
    private function hydrateActorPaginatorFromIds(array $ids, int $total, int $perPage, int $page): LengthAwarePaginator
    {
        if (empty($ids)) {
            return new Paginator(collect(), $total, $perPage, $page, [
                'path' => Paginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]);
        }

        $orderedIds = implode(',', array_map('intval', $ids));
        $items = Actor::query()
            ->withCount(['javs', 'favorites'])
            ->withSum('javs as jav_views', 'views')
            ->whereIn('id', $ids)
            ->orderByRaw("FIELD(id, {$orderedIds})")
            ->get();

        return new Paginator($items, $total, $perPage, $page, [
            'path' => Paginator::resolveCurrentPath(),
            'query' => request()->query(),
        ]);
    }

    private function indexNameFor(string $modelClass): string
    {
        /** @var \Illuminate\Database\Eloquent\Model&\Laravel\Scout\Searchable $model */
        $model = new $modelClass;
        $prefix = (string) config('scout.prefix', '');

        return $prefix.$model->searchableAs();
    }

    private function applyActorTagsFilter(Builder $builder, array $filters): void
    {
        $tags = $filters['tags'] ?? [];
        if (empty($tags)) {
            return;
        }

        if (($filters['tags_mode'] ?? 'any') === 'all') {
            foreach ($tags as $tagName) {
                $builder->whereHas('javs.tags', function (Builder $tagQuery) use ($tagName): void {
                    $tagQuery->where('name', $tagName);
                });
            }

            return;
        }

        $builder->whereHas('javs.tags', function (Builder $tagQuery) use ($tags): void {
            $tagQuery->whereIn('name', $tags);
        });
    }

    private function normalizeDirection(string $direction): string
    {
        return in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';
    }
}
