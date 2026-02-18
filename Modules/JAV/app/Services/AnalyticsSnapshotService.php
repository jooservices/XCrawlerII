<?php

namespace Modules\JAV\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\JAV\Models\Actor;
use Modules\JAV\Models\Interaction;
use Modules\JAV\Models\Jav;
use Modules\JAV\Models\Mongo\AnalyticsSnapshot;
use Modules\JAV\Models\Tag;

class AnalyticsSnapshotService
{
    /**
     * @return array<string, mixed>
     */
    public function getSnapshot(int $days, bool $forceRefresh = false, bool $allowMySqlFallback = true): array
    {
        try {
            $snapshot = AnalyticsSnapshot::query()->where('days', $days)->first();
            $isStale = ! $snapshot
                || ! $snapshot->generated_at
                || $snapshot->generated_at->lt(now()->subMinutes(30));

            if (! $forceRefresh && ! $isStale && is_array($snapshot->payload)) {
                return $snapshot->payload;
            }

            $payload = $this->buildFromMySql($days);

            AnalyticsSnapshot::query()->updateOrCreate(
                ['days' => $days],
                [
                    'generated_at' => now(),
                    'payload' => $payload,
                ]
            );

            return $payload;
        } catch (\Throwable $exception) {
            if (! $allowMySqlFallback) {
                throw $exception;
            }

            Log::warning('Mongo analytics unavailable, falling back to MySQL aggregation.', [
                'days' => $days,
                'error' => $exception->getMessage(),
            ]);

            return $this->buildFromMySql($days);
        }
    }

    /**
     * @param  array<int>  $daysList
     * @return array<int, array<string, mixed>>
     */
    public function syncSnapshots(array $daysList): array
    {
        $synced = [];
        foreach ($daysList as $days) {
            $synced[$days] = $this->getSnapshot($days, true);
        }

        return $synced;
    }

    /**
     * @return array<string, mixed>
     */
    private function buildFromMySql(int $days): array
    {
        $totals = [
            'jav' => Jav::query()->count(),
            'actors' => Actor::query()->count(),
            'tags' => Tag::query()->count(),
        ];

        $today = now()->toDateString();
        $todayCreated = [
            'jav' => Jav::query()->whereDate('created_at', $today)->count(),
            'actors' => Actor::query()->whereDate('created_at', $today)->count(),
            'tags' => Tag::query()->whereDate('created_at', $today)->count(),
        ];

        $dailyCreated = [
            'jav' => $this->buildDailyCounts('jav', $days),
            'actors' => $this->buildDailyCounts('actors', $days),
            'tags' => $this->buildDailyCounts('tags', $days),
        ];
        $providerDailyCreated = $this->buildDailySourceCounts($days);

        $dailyEngagement = [
            'favorites' => $this->buildDailyInteractionCounts(Interaction::ACTION_FAVORITE, $days),
            'watchlists' => $this->buildDailyCounts('watchlists', $days),
            'ratings' => $this->buildDailyInteractionCounts(Interaction::ACTION_RATING, $days),
            'history' => $this->buildDailyCounts('user_jav_history', $days),
        ];

        $providerStats = Jav::query()
            ->selectRaw('COALESCE(NULLIF(source, \'\'), \'unknown\') as source')
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as today_count', [$today])
            ->selectRaw('SUM(CASE WHEN created_at >= ? THEN 1 ELSE 0 END) as window_count', [now()->subDays($days - 1)->startOfDay()])
            ->groupBy('source')
            ->orderByDesc('total_count')
            ->get()
            ->map(static function ($row): array {
                return [
                    'source' => (string) $row->source,
                    'total_count' => (int) $row->total_count,
                    'today_count' => (int) $row->today_count,
                    'window_count' => (int) $row->window_count,
                ];
            })
            ->all();

        $topViewed = Jav::query()
            ->orderByDesc('views')
            ->orderByDesc('downloads')
            ->take(10)
            ->get(['uuid', 'code', 'title', 'views', 'downloads'])
            ->map(static function (Jav $item): array {
                return [
                    'uuid' => $item->uuid,
                    'code' => $item->code,
                    'title' => $item->title,
                    'views' => (int) ($item->views ?? 0),
                    'downloads' => (int) ($item->downloads ?? 0),
                ];
            })
            ->all();

        $topDownloaded = Jav::query()
            ->orderByDesc('downloads')
            ->orderByDesc('views')
            ->take(10)
            ->get(['uuid', 'code', 'title', 'views', 'downloads'])
            ->map(static function (Jav $item): array {
                return [
                    'uuid' => $item->uuid,
                    'code' => $item->code,
                    'title' => $item->title,
                    'views' => (int) ($item->views ?? 0),
                    'downloads' => (int) ($item->downloads ?? 0),
                ];
            })
            ->all();

        $topRated = Jav::query()
            ->withAvg('ratings', 'value')
            ->withCount('ratings')
            ->having('ratings_count', '>', 0)
            ->orderByDesc('ratings_avg_value')
            ->orderByDesc('ratings_count')
            ->take(10)
            ->get(['uuid', 'code', 'title'])
            ->map(static function (Jav $item): array {
                return [
                    'uuid' => $item->uuid,
                    'code' => $item->code,
                    'title' => $item->title,
                    'ratings_avg_rating' => (float) ($item->ratings_avg_rating ?? 0),
                    'ratings_count' => (int) ($item->ratings_count ?? 0),
                ];
            })
            ->all();

        $javActorLinks = Schema::hasTable('jav_actor')
            ? (int) DB::table('jav_actor')->count()
            : 0;
        $javTagLinks = Schema::hasTable('jav_tag')
            ? (int) DB::table('jav_tag')->count()
            : 0;

        $quality = [
            'missing_actors' => Jav::query()->doesntHave('actors')->count(),
            'missing_tags' => Jav::query()->doesntHave('tags')->count(),
            'missing_image' => Jav::query()
                ->where(function ($q): void {
                    $q->whereNull('image')->orWhere('image', '');
                })
                ->count(),
            'missing_date' => Jav::query()->whereNull('date')->count(),
            'orphan_actors' => Actor::query()->doesntHave('javs')->count(),
            'orphan_tags' => Tag::query()->doesntHave('javs')->count(),
            'avg_actors_per_jav' => $totals['jav'] > 0 ? round($javActorLinks / $totals['jav'], 2) : 0.0,
            'avg_tags_per_jav' => $totals['jav'] > 0 ? round($javTagLinks / $totals['jav'], 2) : 0.0,
        ];

        $syncHealth = [
            'pending_jobs' => Schema::hasTable('jobs')
                ? DB::table('jobs')->where('queue', 'jav')->count()
                : 0,
            'failed_jobs_24h' => Schema::hasTable('failed_jobs')
                ? DB::table('failed_jobs')
                    ->where('queue', 'jav')
                    ->where('failed_at', '>=', now()->subDay())
                    ->count()
                : 0,
        ];

        return [
            'days' => $days,
            'generated_at' => now()->toDateTimeString(),
            'totals' => $totals,
            'todayCreated' => $todayCreated,
            'dailyCreated' => $dailyCreated,
            'providerDailyCreated' => $providerDailyCreated,
            'dailyEngagement' => $dailyEngagement,
            'providerStats' => $providerStats,
            'topViewed' => $topViewed,
            'topDownloaded' => $topDownloaded,
            'topRated' => $topRated,
            'quality' => $quality,
            'syncHealth' => $syncHealth,
        ];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function buildDailyCounts(string $table, int $days): array
    {
        $labels = [];
        $values = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->toDateString();
            $values[] = 0;
        }

        if (! Schema::hasTable($table)) {
            return ['labels' => $labels, 'values' => $values];
        }

        $start = now()->subDays($days - 1)->startOfDay();
        $raw = DB::table($table)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('created_at', '>=', $start)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        foreach ($labels as $idx => $day) {
            $values[$idx] = (int) ($raw[$day] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{labels: array<int, string>, values: array<int, int>}
     */
    private function buildDailyInteractionCounts(string $action, int $days): array
    {
        $labels = [];
        $values = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->toDateString();
            $values[] = 0;
        }

        if (! Schema::hasTable('user_interactions')) {
            return ['labels' => $labels, 'values' => $values];
        }

        $start = now()->subDays($days - 1)->startOfDay();
        $raw = DB::table('user_interactions')
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->where('action', $action)
            ->where('created_at', '>=', $start)
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        foreach ($labels as $idx => $day) {
            $values[$idx] = (int) ($raw[$day] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    /**
     * @return array{labels: array<int, string>, series: array<string, array<int, int>>}
     */
    private function buildDailySourceCounts(int $days): array
    {
        $labels = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $labels[] = now()->subDays($i)->toDateString();
        }

        if (! Schema::hasTable('jav')) {
            return ['labels' => $labels, 'series' => []];
        }

        $start = now()->subDays($days - 1)->startOfDay();
        $rows = DB::table('jav')
            ->selectRaw('COALESCE(NULLIF(source, \'\'), \'unknown\') as source')
            ->selectRaw('DATE(created_at) as day')
            ->selectRaw('COUNT(*) as total')
            ->where('created_at', '>=', $start)
            ->groupBy('source', 'day')
            ->orderBy('source')
            ->orderBy('day')
            ->get();

        $sources = $rows->pluck('source')->unique()->values();
        $series = [];
        foreach ($sources as $source) {
            $series[(string) $source] = array_fill(0, $days, 0);
        }

        foreach ($rows as $row) {
            $source = (string) $row->source;
            $day = (string) $row->day;
            $index = array_search($day, $labels, true);
            if ($index !== false) {
                $series[$source][$index] = (int) $row->total;
            }
        }

        return ['labels' => $labels, 'series' => $series];
    }
}
