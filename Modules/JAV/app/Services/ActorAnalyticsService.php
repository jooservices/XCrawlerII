<?php

namespace Modules\JAV\Services;

use Elastic\Elasticsearch\Client;
use Illuminate\Support\Facades\DB;
use Modules\JAV\Exceptions\ElasticsearchUnavailableException;
use Modules\JAV\Models\Actor;
use Modules\JAV\Traits\ElasticsearchHelpers;

class ActorAnalyticsService
{
    use ElasticsearchHelpers;

    private const INDEX = 'actors';

    private const JAV_ACTOR_TABLE = 'jav_actor as ja';

    private const JAV_TAG_TABLE = 'jav_tag as jt';

    private const BASE_DATE_EXPRESSION = 'COALESCE(j.date, j.created_at)';

    /**
     * @return array<string, mixed>
     */
    public function distribution(string $dimension, string $genre, int $size = 10): array
    {
        $this->guardElasticsearch();
        $dimensionConfig = $this->dimensionConfig($dimension, $size);
        $normalizedGenre = mb_strtolower(trim($genre));

        $filteredResponse = $this->client()->search([
            'index' => self::INDEX,
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['term' => ['movie_tags_keyword.keyword' => $normalizedGenre]],
                            ['exists' => ['field' => $dimensionConfig['exists_field']]],
                        ],
                    ],
                ],
                'aggs' => [
                    'segments' => $dimensionConfig['aggregation'],
                ],
            ],
        ])->asArray();

        $overallResponse = $this->client()->search([
            'index' => self::INDEX,
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['exists' => ['field' => $dimensionConfig['exists_field']]],
                        ],
                    ],
                ],
                'aggs' => [
                    'segments' => $dimensionConfig['aggregation'],
                ],
            ],
        ])->asArray();

        $genreTotal = (int) data_get($filteredResponse, 'hits.total.value', 0);
        $overallTotal = (int) data_get($overallResponse, 'hits.total.value', 0);

        $overallMap = [];
        foreach ((array) data_get($overallResponse, 'aggregations.segments.buckets', []) as $bucket) {
            $overallMap[(string) ($bucket['key'] ?? '')] = (int) ($bucket['doc_count'] ?? 0);
        }

        $segments = [];
        foreach ((array) data_get($filteredResponse, 'aggregations.segments.buckets', []) as $bucket) {
            $key = (string) ($bucket['key'] ?? 'unknown');
            $count = (int) ($bucket['doc_count'] ?? 0);
            if ($count === 0) {
                continue;
            }

            $confidence = $genreTotal > 0 ? $count / $genreTotal : 0.0;
            $baseline = $overallTotal > 0
                ? (($overallMap[$key] ?? 0) / $overallTotal)
                : 0.0;
            $lift = $baseline > 0.0 ? $confidence / $baseline : null;

            $segments[] = [
                'segment' => $key,
                'count' => $count,
                'confidence' => round($confidence, 4),
                'lift' => $lift !== null ? round($lift, 4) : null,
            ];
        }

        usort($segments, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return [
            'dimension' => $dimension,
            'genre' => $normalizedGenre,
            'genre_total' => $genreTotal,
            'overall_total' => $overallTotal,
            'segments' => $segments,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function association(string $segmentType, string $segmentValue, int $size = 10, int $minSupport = 1): array
    {
        $this->guardElasticsearch();
        $segmentFilter = $this->segmentFilter($segmentType, $segmentValue);

        $segmentResponse = $this->client()->search([
            'index' => self::INDEX,
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['exists' => ['field' => 'movie_tags_keyword.keyword']],
                            $segmentFilter,
                        ],
                    ],
                ],
                'aggs' => [
                    'genres' => [
                        'terms' => [
                            'field' => 'movie_tags_keyword.keyword',
                            'size' => $size,
                            'min_doc_count' => $minSupport,
                        ],
                    ],
                ],
            ],
        ])->asArray();

        $baselineResponse = $this->client()->search([
            'index' => self::INDEX,
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'filter' => [
                            ['exists' => ['field' => 'movie_tags_keyword.keyword']],
                        ],
                    ],
                ],
                'aggs' => [
                    'genres' => [
                        'terms' => [
                            'field' => 'movie_tags_keyword.keyword',
                            'size' => 100,
                        ],
                    ],
                ],
            ],
        ])->asArray();

        $segmentTotal = (int) data_get($segmentResponse, 'hits.total.value', 0);
        $allTotal = (int) data_get($baselineResponse, 'hits.total.value', 0);

        $baselineMap = [];
        foreach ((array) data_get($baselineResponse, 'aggregations.genres.buckets', []) as $bucket) {
            $baselineMap[(string) ($bucket['key'] ?? '')] = (int) ($bucket['doc_count'] ?? 0);
        }

        $rules = [];
        foreach ((array) data_get($segmentResponse, 'aggregations.genres.buckets', []) as $bucket) {
            $genre = (string) ($bucket['key'] ?? 'unknown');
            $count = (int) ($bucket['doc_count'] ?? 0);

            $confidence = $segmentTotal > 0 ? $count / $segmentTotal : 0.0;
            $baseline = $allTotal > 0
                ? (($baselineMap[$genre] ?? 0) / $allTotal)
                : 0.0;
            $lift = $baseline > 0.0 ? $confidence / $baseline : null;
            $support = $allTotal > 0 ? $count / $allTotal : 0.0;

            $rules[] = [
                'genre' => $genre,
                'count' => $count,
                'support' => round($support, 4),
                'confidence' => round($confidence, 4),
                'lift' => $lift !== null ? round($lift, 4) : null,
            ];
        }

        usort($rules, static fn (array $a, array $b): int => $b['confidence'] <=> $a['confidence']);

        return [
            'segment_type' => $segmentType,
            'segment_value' => $segmentValue,
            'segment_total' => $segmentTotal,
            'population_total' => $allTotal,
            'rules' => $rules,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function trends(string $dimension, ?string $genre = null, string $interval = 'month', int $size = 5): array
    {
        $this->guardElasticsearch();
        $dimensionConfig = $this->dimensionConfig($dimension, $size);

        $filters = [];
        if ($genre !== null && trim($genre) !== '') {
            $filters[] = ['term' => ['movie_tags_keyword.keyword' => mb_strtolower(trim($genre))]];
        }

        $response = $this->client()->search([
            'index' => self::INDEX,
            'body' => [
                'size' => 0,
                'query' => [
                    'bool' => [
                        'filter' => array_merge($filters, [
                            ['exists' => ['field' => 'created_at']],
                            ['exists' => ['field' => $dimensionConfig['exists_field']]],
                        ]),
                    ],
                ],
                'aggs' => [
                    'timeline' => [
                        'date_histogram' => [
                            'field' => 'created_at',
                            'calendar_interval' => $interval,
                            'min_doc_count' => 1,
                        ],
                        'aggs' => [
                            'segments' => $dimensionConfig['aggregation'],
                        ],
                    ],
                ],
            ],
        ])->asArray();

        $periods = [];
        foreach ((array) data_get($response, 'aggregations.timeline.buckets', []) as $timelineBucket) {
            $total = (int) ($timelineBucket['doc_count'] ?? 0);
            $segmentBuckets = (array) ($timelineBucket['segments']['buckets'] ?? []);
            $segmentMap = [];
            foreach ($segmentBuckets as $segmentBucket) {
                $segmentMap[(string) ($segmentBucket['key'] ?? 'unknown')] = (int) ($segmentBucket['doc_count'] ?? 0);
            }

            arsort($segmentMap);
            $topSegment = array_key_first($segmentMap);
            $topCount = $topSegment !== null ? (int) ($segmentMap[$topSegment] ?? 0) : 0;

            $periods[] = [
                'period' => (string) ($timelineBucket['key_as_string'] ?? ''),
                'total' => $total,
                'segments' => $segmentMap,
                'top_segment' => $topSegment,
                'top_count' => $topCount,
                'top_share' => $total > 0 ? round($topCount / $total, 4) : 0.0,
            ];
        }

        return [
            'dimension' => $dimension,
            'genre' => $genre,
            'interval' => $interval,
            'periods' => $periods,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    public function predictGenres(array $profile, int $size = 5): array
    {
        $this->guardElasticsearch();

        $should = [];
        $age = isset($profile['age']) ? (int) $profile['age'] : null;
        if ($age !== null) {
            $should[] = [
                'range' => [
                    'age' => [
                        'gte' => max(16, $age - 2),
                        'lte' => min(80, $age + 2),
                        'boost' => 2,
                    ],
                ],
            ];
        }

        $bloodType = isset($profile['blood_type']) ? trim((string) $profile['blood_type']) : '';
        if ($bloodType !== '') {
            $should[] = [
                'bool' => [
                    'should' => [
                        ['term' => ['xcity_blood_type.keyword' => $bloodType]],
                        ['term' => ['xcity_blood_type.keyword' => mb_strtolower($bloodType)]],
                        ['term' => ['xcity_blood_type.keyword' => mb_strtoupper($bloodType)]],
                    ],
                    'minimum_should_match' => 1,
                    'boost' => 2,
                ],
            ];
        }

        $birthplace = isset($profile['birthplace']) ? trim((string) $profile['birthplace']) : '';
        if ($birthplace !== '') {
            $should[] = [
                'match_phrase' => [
                    'xcity_city_of_birth' => [
                        'query' => $birthplace,
                        'boost' => 1.5,
                    ],
                ],
            ];
        }

        $movieTags = collect((array) ($profile['movie_tags'] ?? []))
            ->map(static fn (mixed $tag): string => mb_strtolower(trim((string) $tag)))
            ->filter(static fn (string $tag): bool => $tag !== '')
            ->values()
            ->all();
        if ($movieTags !== []) {
            $should[] = [
                'terms' => [
                    'movie_tags_keyword.keyword' => $movieTags,
                    'boost' => 1.2,
                ],
            ];
        }

        $query = [
            'bool' => [
                'filter' => [
                    ['exists' => ['field' => 'movie_tags_keyword.keyword']],
                ],
            ],
        ];
        if ($should !== []) {
            $query['bool']['should'] = $should;
            $query['bool']['minimum_should_match'] = 1;
        }

        $response = $this->client()->search([
            'index' => self::INDEX,
            'body' => [
                'size' => 0,
                'query' => $query,
                'aggs' => [
                    'genres' => [
                        'terms' => [
                            'field' => 'movie_tags_keyword.keyword',
                            'size' => $size,
                        ],
                    ],
                    'sample_actors' => [
                        'top_hits' => [
                            'size' => 5,
                            '_source' => [
                                'includes' => [
                                    'uuid',
                                    'name',
                                    'age',
                                    'xcity_blood_type',
                                    'xcity_city_of_birth',
                                    'movie_tags_keyword',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ])->asArray();

        $matchedActors = (int) data_get($response, 'hits.total.value', 0);
        $genreBuckets = (array) data_get($response, 'aggregations.genres.buckets', []);
        $genreCountTotal = array_sum(array_map(static fn (array $bucket): int => (int) ($bucket['doc_count'] ?? 0), $genreBuckets));

        $predictions = [];
        foreach ($genreBuckets as $bucket) {
            $count = (int) ($bucket['doc_count'] ?? 0);
            $probability = $genreCountTotal > 0 ? $count / $genreCountTotal : 0.0;
            $predictions[] = [
                'genre' => (string) ($bucket['key'] ?? 'unknown'),
                'count' => $count,
                'probability' => round($probability, 4),
            ];
        }

        return [
            'input_profile' => [
                'age' => $age,
                'blood_type' => $bloodType !== '' ? $bloodType : null,
                'birthplace' => $birthplace !== '' ? $birthplace : null,
                'movie_tags' => $movieTags,
            ],
            'matched_actors' => $matchedActors,
            'predictions' => $predictions,
            'sample_actors' => collect((array) data_get($response, 'aggregations.sample_actors.hits.hits', []))
                ->map(static fn (array $hit): array => (array) ($hit['_source'] ?? []))
                ->values()
                ->all(),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function quality(): array
    {
        $this->guardElasticsearch();

        $response = $this->client()->search([
            'index' => self::INDEX,
            'body' => [
                'size' => 0,
                'aggs' => [
                    'with_genres' => ['filter' => ['exists' => ['field' => 'movie_tags_keyword.keyword']]],
                    'with_age' => ['filter' => ['exists' => ['field' => 'age']]],
                    'with_blood_type' => ['filter' => ['exists' => ['field' => 'xcity_blood_type.keyword']]],
                    'with_birthplace' => ['filter' => ['exists' => ['field' => 'xcity_city_of_birth.keyword']]],
                    'with_birth_date' => ['filter' => ['exists' => ['field' => 'birth_date']]],
                ],
            ],
        ])->asArray();

        $total = (int) data_get($response, 'hits.total.value', 0);
        $metrics = [
            'genres' => (int) data_get($response, 'aggregations.with_genres.doc_count', 0),
            'age' => (int) data_get($response, 'aggregations.with_age.doc_count', 0),
            'blood_type' => (int) data_get($response, 'aggregations.with_blood_type.doc_count', 0),
            'birthplace' => (int) data_get($response, 'aggregations.with_birthplace.doc_count', 0),
            'birth_date' => (int) data_get($response, 'aggregations.with_birth_date.doc_count', 0),
        ];

        $coverage = [];
        foreach ($metrics as $key => $value) {
            $coverage[$key] = [
                'count' => $value,
                'rate' => $total > 0 ? round($value / $total, 4) : 0.0,
                'missing' => max($total - $value, 0),
            ];
        }

        return [
            'total_actors' => $total,
            'coverage' => $coverage,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function overview(int $size = 8): array
    {
        $this->guardElasticsearch();

        $response = $this->client()->search([
            'index' => self::INDEX,
            'body' => [
                'size' => 0,
                'aggs' => [
                    'top_genres' => [
                        'terms' => [
                            'field' => 'movie_tags_keyword.keyword',
                            'size' => $size,
                        ],
                    ],
                    'age_buckets' => [
                        'range' => [
                            'field' => 'age',
                            'ranges' => [
                                ['key' => '18-22', 'from' => 18, 'to' => 23],
                                ['key' => '23-27', 'from' => 23, 'to' => 28],
                                ['key' => '28-32', 'from' => 28, 'to' => 33],
                                ['key' => '33-37', 'from' => 33, 'to' => 38],
                                ['key' => '38+', 'from' => 38],
                            ],
                        ],
                    ],
                    'blood_types' => [
                        'terms' => [
                            'field' => 'xcity_blood_type.keyword',
                            'size' => 8,
                            'missing' => 'unknown',
                        ],
                    ],
                    'actors_timeline' => [
                        'date_histogram' => [
                            'field' => 'created_at',
                            'calendar_interval' => 'month',
                            'min_doc_count' => 1,
                        ],
                    ],
                ],
            ],
        ])->asArray();

        $topGenres = collect((array) data_get($response, 'aggregations.top_genres.buckets', []))
            ->map(static fn (array $bucket): array => [
                'genre' => (string) ($bucket['key'] ?? 'unknown'),
                'count' => (int) ($bucket['doc_count'] ?? 0),
            ])
            ->values()
            ->all();

        $ageBuckets = collect((array) data_get($response, 'aggregations.age_buckets.buckets', []))
            ->map(static fn (array $bucket): array => [
                'bucket' => (string) ($bucket['key'] ?? 'unknown'),
                'count' => (int) ($bucket['doc_count'] ?? 0),
            ])
            ->values()
            ->all();

        $bloodTypes = collect((array) data_get($response, 'aggregations.blood_types.buckets', []))
            ->map(static fn (array $bucket): array => [
                'type' => (string) ($bucket['key'] ?? 'unknown'),
                'count' => (int) ($bucket['doc_count'] ?? 0),
            ])
            ->values()
            ->all();

        $timeline = collect((array) data_get($response, 'aggregations.actors_timeline.buckets', []))
            ->map(static fn (array $bucket): array => [
                'period' => (string) ($bucket['key_as_string'] ?? ''),
                'count' => (int) ($bucket['doc_count'] ?? 0),
            ])
            ->values()
            ->all();

        return [
            'total_actors' => (int) data_get($response, 'hits.total.value', 0),
            'top_genres' => $topGenres,
            'age_buckets' => $ageBuckets,
            'blood_types' => $bloodTypes,
            'actors_timeline' => $timeline,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function suggestions(string $type, string $query = '', int $limit = 8): array
    {
        $normalizedQuery = trim($query);
        $limit = max(1, min(20, $limit));

        $items = match ($type) {
            'actor' => $this->suggestActors($normalizedQuery, $limit),
            'birthplace' => $this->suggestBirthplaces($normalizedQuery, $limit),
            'blood_type' => collect(['A', 'B', 'O', 'AB'])
                ->filter(static fn (string $bloodType): bool => $normalizedQuery === '' || str_contains($bloodType, mb_strtoupper($normalizedQuery)))
                ->map(static fn (string $bloodType): array => [
                    'label' => $bloodType,
                    'value' => $bloodType,
                ])
                ->values()
                ->all(),
            default => $this->suggestGenres($normalizedQuery, $limit),
        };

        return [
            'type' => $type,
            'query' => $normalizedQuery,
            'items' => $items,
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function actorInsights(string $actorUuid, int $size = 5): ?array
    {
        $actor = Actor::query()
            ->where('uuid', $actorUuid)
            ->first();

        if ($actor === null) {
            return null;
        }

        $profile = [
            'age' => $actor->age,
            'blood_type' => $actor->xcity_blood_type,
            'birthplace' => $actor->xcity_city_of_birth,
            'movie_tags' => $this->actorTopTags($actor->id, 20),
        ];

        $actorGenres = DB::table(self::JAV_ACTOR_TABLE)
            ->join(self::JAV_TAG_TABLE, 'jt.jav_id', '=', 'ja.jav_id')
            ->join('tags as t', 't.id', '=', 'jt.tag_id')
            ->where('ja.actor_id', $actor->id)
            ->groupBy('t.name')
            ->selectRaw('t.name as genre')
            ->selectRaw('COUNT(*) as count')
            ->orderByRaw('COUNT(*) DESC')
            ->orderBy('t.name')
            ->get()
            ->map(static fn (object $row): array => [
                'genre' => (string) ($row->genre ?? ''),
                'count' => (int) ($row->count ?? 0),
            ])
            ->filter(static fn (array $row): bool => $row['genre'] !== '')
            ->values()
            ->all();

        try {
            $prediction = $this->predictGenres($profile, $size);
        } catch (\Throwable) {
            $prediction = [
                'predictions' => [],
                'matched_actors' => 0,
            ];
        }

        return [
            'actor' => [
                'id' => $actor->id,
                'uuid' => $actor->uuid,
                'name' => $actor->name,
                'age' => $actor->age,
                'blood_type' => $actor->xcity_blood_type,
                'birthplace' => $actor->xcity_city_of_birth,
                'javs_count' => (int) $actor->javs()->count(),
            ],
            'predicted_genres' => $prediction['predictions'] ?? [],
            'matched_actors' => (int) ($prediction['matched_actors'] ?? 0),
            'actor_genres' => $actorGenres,
            'actor_genres_total' => count($actorGenres),
            'genre_period_counts' => [
                'week' => $this->periodDistinctGenreCounts($actor->id, 'week'),
                'month' => $this->periodDistinctGenreCounts($actor->id, 'month'),
                'year' => $this->periodDistinctGenreCounts($actor->id, 'year'),
            ],
            'movie_period_counts' => [
                'week' => $this->periodMovieCounts($actor->id, 'week'),
                'month' => $this->periodMovieCounts($actor->id, 'month'),
                'year' => $this->periodMovieCounts($actor->id, 'year'),
            ],
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function dimensionConfig(string $dimension, int $size): array
    {
        return match ($dimension) {
            'age_bucket' => [
                'exists_field' => 'age',
                'aggregation' => [
                    'range' => [
                        'field' => 'age',
                        'ranges' => [
                            ['key' => '18-22', 'from' => 18, 'to' => 23],
                            ['key' => '23-27', 'from' => 23, 'to' => 28],
                            ['key' => '28-32', 'from' => 28, 'to' => 33],
                            ['key' => '33-37', 'from' => 33, 'to' => 38],
                            ['key' => '38+', 'from' => 38],
                        ],
                    ],
                ],
            ],
            'birthplace' => [
                'exists_field' => 'xcity_city_of_birth.keyword',
                'aggregation' => [
                    'terms' => [
                        'field' => 'xcity_city_of_birth.keyword',
                        'size' => $size,
                        'missing' => 'unknown',
                    ],
                ],
            ],
            default => [
                'exists_field' => 'xcity_blood_type.keyword',
                'aggregation' => [
                    'terms' => [
                        'field' => 'xcity_blood_type.keyword',
                        'size' => $size,
                        'missing' => 'unknown',
                    ],
                ],
            ],
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function segmentFilter(string $segmentType, string $segmentValue): array
    {
        $value = trim($segmentValue);

        if ($segmentType === 'age_bucket') {
            [$from, $to] = $this->parseAgeBucket($value);

            return [
                'range' => [
                    'age' => [
                        'gte' => $from,
                        'lt' => $to,
                    ],
                ],
            ];
        }

        if ($segmentType === 'birthplace') {
            return [
                'match_phrase' => [
                    'xcity_city_of_birth' => $value,
                ],
            ];
        }

        return [
            'bool' => [
                'should' => [
                    ['term' => ['xcity_blood_type.keyword' => $value]],
                    ['term' => ['xcity_blood_type.keyword' => mb_strtolower($value)]],
                    ['term' => ['xcity_blood_type.keyword' => mb_strtoupper($value)]],
                ],
                'minimum_should_match' => 1,
            ],
        ];
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function parseAgeBucket(string $bucket): array
    {
        $normalized = str_replace(' ', '', $bucket);
        if (preg_match('/^(\d+)\-(\d+)$/', $normalized, $matches) !== 1) {
            return [18, 100];
        }

        $from = (int) $matches[1];
        $to = (int) $matches[2] + 1;

        return [$from, $to];
    }

    /**
     * @return array<int, string>
     */
    private function actorTopTags(int $actorId, int $limit = 20): array
    {
        return DB::table(self::JAV_ACTOR_TABLE)
            ->join(self::JAV_TAG_TABLE, 'jt.jav_id', '=', 'ja.jav_id')
            ->join('tags as t', 't.id', '=', 'jt.tag_id')
            ->where('ja.actor_id', $actorId)
            ->groupBy('t.name')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit)
            ->pluck('t.name')
            ->map(static fn (mixed $name): string => mb_strtolower(trim((string) $name)))
            ->filter(static fn (string $name): bool => $name !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function periodDistinctGenreCounts(int $actorId, string $granularity): array
    {
        $periodExpression = $this->periodExpression($granularity);
        $rows = DB::table(self::JAV_ACTOR_TABLE)
            ->join('jav as j', 'j.id', '=', 'ja.jav_id')
            ->join(self::JAV_TAG_TABLE, 'jt.jav_id', '=', 'j.id')
            ->where('ja.actor_id', $actorId)
            ->selectRaw("{$periodExpression} as period")
            ->selectRaw('COUNT(DISTINCT jt.tag_id) as genre_count')
            ->whereRaw(self::BASE_DATE_EXPRESSION.' IS NOT NULL')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $points = collect($rows)->map(static function (object $row): array {
            return [
                'period' => (string) $row->period,
                'count' => (int) ($row->genre_count ?? 0),
            ];
        })->values()->all();

        $total = array_sum(array_map(static fn (array $point): int => $point['count'], $points));
        $countPeriods = count($points);

        return [
            'points' => $points,
            'total' => $total,
            'avg' => $countPeriods > 0 ? round($total / $countPeriods, 2) : 0.0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function periodMovieCounts(int $actorId, string $granularity): array
    {
        $periodExpression = $this->periodExpression($granularity);
        $rows = DB::table(self::JAV_ACTOR_TABLE)
            ->join('jav as j', 'j.id', '=', 'ja.jav_id')
            ->where('ja.actor_id', $actorId)
            ->selectRaw("{$periodExpression} as period")
            ->selectRaw('COUNT(DISTINCT j.id) as movie_count')
            ->whereRaw(self::BASE_DATE_EXPRESSION.' IS NOT NULL')
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $points = collect($rows)->map(static function (object $row): array {
            return [
                'period' => (string) $row->period,
                'count' => (int) ($row->movie_count ?? 0),
            ];
        })->values()->all();

        $total = array_sum(array_map(static fn (array $point): int => $point['count'], $points));
        $countPeriods = count($points);

        return [
            'points' => $points,
            'total' => $total,
            'avg' => $countPeriods > 0 ? round($total / $countPeriods, 2) : 0.0,
        ];
    }

    private function periodExpression(string $granularity): string
    {
        $baseDate = self::BASE_DATE_EXPRESSION;

        return match ($granularity) {
            'week' => "DATE_FORMAT({$baseDate}, '%x-W%v')",
            'year' => "DATE_FORMAT({$baseDate}, '%Y')",
            default => "DATE_FORMAT({$baseDate}, '%Y-%m')",
        };
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function suggestActors(string $query, int $limit): array
    {
        $builder = Actor::query()->select(['uuid', 'name'])->orderBy('name');

        if ($query !== '') {
            $like = '%'.addcslashes($query, '\\%_').'%';
            $builder->where(function ($innerBuilder) use ($like): void {
                $innerBuilder->where('name', 'like', $like)
                    ->orWhere('uuid', 'like', $like);
            });
        }

        return $builder
            ->limit($limit)
            ->get()
            ->map(static fn (Actor $actor): array => [
                'label' => trim((string) $actor->name).' ('.(string) $actor->uuid.')',
                'value' => (string) $actor->uuid,
                'name' => trim((string) $actor->name),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function suggestGenres(string $query, int $limit): array
    {
        $builder = DB::table('tags')->select('name')->orderBy('name');

        if ($query !== '') {
            $like = '%'.addcslashes($query, '\\%_').'%';
            $builder->where('name', 'like', $like);
        }

        return $builder
            ->limit($limit)
            ->pluck('name')
            ->map(static fn (mixed $name): array => [
                'label' => trim((string) $name),
                'value' => mb_strtolower(trim((string) $name)),
            ])
            ->filter(static fn (array $row): bool => $row['label'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, string>>
     */
    private function suggestBirthplaces(string $query, int $limit): array
    {
        $builder = Actor::query()
            ->select('xcity_city_of_birth')
            ->whereNotNull('xcity_city_of_birth')
            ->where('xcity_city_of_birth', '!=', '')
            ->distinct()
            ->orderBy('xcity_city_of_birth');

        if ($query !== '') {
            $like = '%'.addcslashes($query, '\\%_').'%';
            $builder->where('xcity_city_of_birth', 'like', $like);
        }

        return $builder
            ->limit($limit)
            ->pluck('xcity_city_of_birth')
            ->map(static fn (mixed $value): array => [
                'label' => trim((string) $value),
                'value' => trim((string) $value),
            ])
            ->filter(static fn (array $row): bool => $row['label'] !== '')
            ->values()
            ->all();
    }

    private function guardElasticsearch(): void
    {
        if (! $this->isElasticsearchAvailable(self::INDEX)) {
            throw new ElasticsearchUnavailableException('Elasticsearch actors index is unavailable.');
        }
    }

    private function client(): Client
    {
        return app(Client::class);
    }
}
