<?php

namespace Modules\JAV\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\JAV\Models\Actor;
use Modules\JAV\Services\AnalyticsSnapshotService;
use Modules\JAV\Services\JobTelemetryAnalyticsService;

class JavAnalyticsReportCommand extends Command
{
    protected $signature = 'jav:analytics:report
                            {--days=14 : Analytics window in days}
                            {--limit=10 : Top rows to display for ranking sections}
                            {--telemetry-window=60 : Job telemetry window (minutes)}
                            {--json : Print report as JSON for automation}';

    protected $description = 'Show comprehensive JAV analytics in CLI (providers, XCity, quality, engagement, tops, queue telemetry).';

    public function handle(AnalyticsSnapshotService $snapshotService, JobTelemetryAnalyticsService $telemetryService): int
    {
        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');
        $telemetryWindow = (int) $this->option('telemetry-window');

        $validationError = $this->validateOptions($days, $limit, $telemetryWindow);
        if ($validationError !== null) {
            $this->error($validationError);

            return self::INVALID;
        }

        try {
            $snapshot = $snapshotService->getSnapshot($days);
        } catch (\Throwable $exception) {
            $this->error('Failed loading analytics snapshot: '.$exception->getMessage());

            return self::FAILURE;
        }

        $providerRows = $this->buildProviderRows($snapshot);
        $xcity = $this->buildXcityStats();
        $telemetry = $this->buildTelemetrySafely($telemetryService, $telemetryWindow, $limit);

        $report = [
            'generated_at' => now()->toDateTimeString(),
            'days' => $days,
            'snapshot_generated_at' => (string) ($snapshot['generated_at'] ?? ''),
            'totals' => $snapshot['totals'] ?? ['jav' => 0, 'actors' => 0, 'tags' => 0],
            'todayCreated' => $snapshot['todayCreated'] ?? ['jav' => 0, 'actors' => 0, 'tags' => 0],
            'provider_breakdown' => $providerRows,
            'provider_stats' => $snapshot['providerStats'] ?? [],
            'quality' => $snapshot['quality'] ?? [],
            'syncHealth' => $snapshot['syncHealth'] ?? [],
            'xcity' => $xcity,
            'dailyCreated' => $snapshot['dailyCreated'] ?? [],
            'dailyEngagement' => $snapshot['dailyEngagement'] ?? [],
            'topViewed' => array_slice((array) ($snapshot['topViewed'] ?? []), 0, $limit),
            'topDownloaded' => array_slice((array) ($snapshot['topDownloaded'] ?? []), 0, $limit),
            'topRated' => array_slice((array) ($snapshot['topRated'] ?? []), 0, $limit),
            'telemetry' => $telemetry,
        ];

        if ((bool) $this->option('json')) {
            $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->renderHumanReadable($report, $limit, $days);
        }

        return self::SUCCESS;
    }

    private function validateOptions(int $days, int $limit, int $telemetryWindow): ?string
    {
        $error = null;

        if ($days < 1 || $days > 365) {
            $error = 'Invalid --days value. Allowed range: 1..365';
        } elseif ($limit < 1 || $limit > 100) {
            $error = 'Invalid --limit value. Allowed range: 1..100';
        } elseif ($telemetryWindow < 5 || $telemetryWindow > 1440) {
            $error = 'Invalid --telemetry-window value. Allowed range: 5..1440';
        }

        return $error;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     * @return array<int, array<string, int|float|string>>
     */
    private function buildProviderRows(array $snapshot): array
    {
        $rawRows = collect((array) ($snapshot['providerStats'] ?? []));
        $bySource = $rawRows->keyBy(static fn (array $row): string => (string) ($row['source'] ?? 'unknown'));
        $totalJav = (int) ($snapshot['totals']['jav'] ?? 0);

        $providers = ['onejav', '141jav', 'ffjav', 'missav'];
        $rows = [];

        foreach ($providers as $source) {
            $row = (array) ($bySource->get($source) ?? []);
            $total = (int) ($row['total_count'] ?? 0);

            $rows[] = [
                'source' => $source,
                'total_count' => $total,
                'today_count' => (int) ($row['today_count'] ?? 0),
                'window_count' => (int) ($row['window_count'] ?? 0),
                'share_percent' => $totalJav > 0 ? round(($total / $totalJav) * 100, 2) : 0.0,
            ];
        }

        $knownSet = collect($providers)->flip();
        $others = $rawRows
            ->filter(static fn (array $row): bool => ! $knownSet->has((string) ($row['source'] ?? 'unknown')))
            ->map(static function (array $row) use ($totalJav): array {
                $total = (int) ($row['total_count'] ?? 0);

                return [
                    'source' => (string) ($row['source'] ?? 'unknown'),
                    'total_count' => $total,
                    'today_count' => (int) ($row['today_count'] ?? 0),
                    'window_count' => (int) ($row['window_count'] ?? 0),
                    'share_percent' => $totalJav > 0 ? round(($total / $totalJav) * 100, 2) : 0.0,
                ];
            })
            ->sortByDesc('total_count')
            ->values()
            ->all();

        return array_merge($rows, $others);
    }

    /**
     * @return array<string, int|float>
     */
    private function buildXcityStats(): array
    {
        $totalActors = Actor::query()->count();
        $today = now()->toDateString();

        $withXcityId = Actor::query()
            ->whereNotNull('xcity_id')
            ->where('xcity_id', '!=', '')
            ->count();

        $withXcityUrl = Actor::query()
            ->whereNotNull('xcity_url')
            ->where('xcity_url', '!=', '')
            ->count();

        $withXcityCover = Actor::query()
            ->whereNotNull('xcity_cover')
            ->where('xcity_cover', '!=', '')
            ->count();

        $withBirthDate = Actor::query()->whereNotNull('xcity_birth_date')->count();
        $withSize = Actor::query()
            ->whereNotNull('xcity_size')
            ->where('xcity_size', '!=', '')
            ->count();

        $syncedToday = Actor::query()->whereDate('xcity_synced_at', $today)->count();
        $actorsCreatedToday = Actor::query()->whereDate('created_at', $today)->count();

        $sourceRows = Schema::hasTable('actor_profile_sources')
            ? DB::table('actor_profile_sources')
                ->selectRaw("COALESCE(NULLIF(source, ''), 'unknown') as source")
                ->selectRaw('COUNT(*) as total_count')
                ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as today_count', [$today])
                ->groupBy('source')
                ->orderByDesc('total_count')
                ->get()
            : collect();

        $xcitySource = $sourceRows->firstWhere('source', 'xcity');

        return [
            'actors_total' => (int) $totalActors,
            'actors_created_today' => (int) $actorsCreatedToday,
            'with_xcity_id' => (int) $withXcityId,
            'with_xcity_url' => (int) $withXcityUrl,
            'with_xcity_cover' => (int) $withXcityCover,
            'with_xcity_birth_date' => (int) $withBirthDate,
            'with_xcity_size' => (int) $withSize,
            'xcity_synced_today' => (int) $syncedToday,
            'xcity_id_coverage_percent' => $totalActors > 0 ? round(($withXcityId / $totalActors) * 100, 2) : 0.0,
            'xcity_profile_source_total' => (int) (($xcitySource->total_count ?? 0) ?: 0),
            'xcity_profile_source_today' => (int) (($xcitySource->today_count ?? 0) ?: 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTelemetrySafely(JobTelemetryAnalyticsService $telemetryService, int $windowMinutes, int $limit): array
    {
        try {
            return $telemetryService->summary([
                'window_minutes' => $windowMinutes,
                'limit' => $limit,
            ]);
        } catch (\Throwable $exception) {
            return [
                'unavailable' => true,
                'error' => $exception->getMessage(),
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $report
     */
    private function renderHumanReadable(array $report, int $limit, int $days): void
    {
        $this->info(sprintf('JAV Analytics Report (window=%d days)', $days));
        $this->line('Generated at: '.(string) ($report['generated_at'] ?? ''));
        $this->line('Snapshot generated at: '.(string) ($report['snapshot_generated_at'] ?? ''));
        $this->newLine();

        $totals = (array) ($report['totals'] ?? []);
        $today = (array) ($report['todayCreated'] ?? []);
        $syncHealth = (array) ($report['syncHealth'] ?? []);

        $this->info('Overview');
        $this->table(['Metric', 'Value'], [
            ['Total JAV', (int) ($totals['jav'] ?? 0)],
            ['Total Actors', (int) ($totals['actors'] ?? 0)],
            ['Total Tags', (int) ($totals['tags'] ?? 0)],
            ['Created Today (JAV)', (int) ($today['jav'] ?? 0)],
            ['Created Today (Actors)', (int) ($today['actors'] ?? 0)],
            ['Created Today (Tags)', (int) ($today['tags'] ?? 0)],
            ['Pending Jobs (queue=jav)', (int) ($syncHealth['pending_jobs'] ?? 0)],
            ['Failed Jobs 24h (queue=jav)', (int) ($syncHealth['failed_jobs_24h'] ?? 0)],
        ]);
        $this->newLine();

        $this->info('Providers (onejav / 141jav / ffjav + others)');
        $providerRows = collect((array) ($report['provider_breakdown'] ?? []))
            ->map(static function (array $row): array {
                return [
                    (string) ($row['source'] ?? 'unknown'),
                    (int) ($row['today_count'] ?? 0),
                    (int) ($row['window_count'] ?? 0),
                    (int) ($row['total_count'] ?? 0),
                    (float) ($row['share_percent'] ?? 0.0),
                ];
            })
            ->all();
        $this->table(['Source', 'Today', 'Window', 'Total', 'Share %'], $providerRows);
        $this->newLine();

        $xcity = (array) ($report['xcity'] ?? []);
        $this->info('XCity Coverage');
        $this->table(['Metric', 'Value'], [
            ['Actors total', (int) ($xcity['actors_total'] ?? 0)],
            ['Actors created today', (int) ($xcity['actors_created_today'] ?? 0)],
            ['Actors with xcity_id', (int) ($xcity['with_xcity_id'] ?? 0)],
            ['Actors with xcity_url', (int) ($xcity['with_xcity_url'] ?? 0)],
            ['Actors with xcity_cover', (int) ($xcity['with_xcity_cover'] ?? 0)],
            ['Actors with xcity_birth_date', (int) ($xcity['with_xcity_birth_date'] ?? 0)],
            ['Actors with xcity_size', (int) ($xcity['with_xcity_size'] ?? 0)],
            ['xcity synced today', (int) ($xcity['xcity_synced_today'] ?? 0)],
            ['xcity_id coverage %', (float) ($xcity['xcity_id_coverage_percent'] ?? 0.0)],
            ['xcity profile source total', (int) ($xcity['xcity_profile_source_total'] ?? 0)],
            ['xcity profile source today', (int) ($xcity['xcity_profile_source_today'] ?? 0)],
        ]);
        $this->newLine();

        $quality = (array) ($report['quality'] ?? []);
        $this->info('Data Quality');
        $this->table(['Metric', 'Value'], [
            ['JAV missing actors', (int) ($quality['missing_actors'] ?? 0)],
            ['JAV missing tags', (int) ($quality['missing_tags'] ?? 0)],
            ['JAV missing image', (int) ($quality['missing_image'] ?? 0)],
            ['JAV missing date', (int) ($quality['missing_date'] ?? 0)],
            ['Orphan actors', (int) ($quality['orphan_actors'] ?? 0)],
            ['Orphan tags', (int) ($quality['orphan_tags'] ?? 0)],
            ['Avg actors/JAV', (float) ($quality['avg_actors_per_jav'] ?? 0.0)],
            ['Avg tags/JAV', (float) ($quality['avg_tags_per_jav'] ?? 0.0)],
        ]);
        $this->newLine();

        $this->info('Daily Activity');
        $dailyRows = $this->buildDailyActivityRows($report);
        $this->table(
            ['Date', 'JAV+', 'Actors+', 'Tags+', 'Favorites+', 'Watchlists+', 'Ratings+', 'History+'],
            $dailyRows
        );
        $this->newLine();

        $this->renderTopTable('Top Viewed', (array) ($report['topViewed'] ?? []), ['Code', 'Title', 'Views', 'Downloads'], $limit, function (array $row): array {
            return [
                (string) ($row['code'] ?? ''),
                (string) ($row['title'] ?? ''),
                (int) ($row['views'] ?? 0),
                (int) ($row['downloads'] ?? 0),
            ];
        });

        $this->renderTopTable('Top Downloaded', (array) ($report['topDownloaded'] ?? []), ['Code', 'Title', 'Downloads', 'Views'], $limit, function (array $row): array {
            return [
                (string) ($row['code'] ?? ''),
                (string) ($row['title'] ?? ''),
                (int) ($row['downloads'] ?? 0),
                (int) ($row['views'] ?? 0),
            ];
        });

        $this->renderTopTable('Top Rated', (array) ($report['topRated'] ?? []), ['Code', 'Title', 'Avg Rating', 'Ratings'], $limit, function (array $row): array {
            return [
                (string) ($row['code'] ?? ''),
                (string) ($row['title'] ?? ''),
                (float) ($row['ratings_avg_rating'] ?? 0),
                (int) ($row['ratings_count'] ?? 0),
            ];
        });

        $this->renderTelemetry((array) ($report['telemetry'] ?? []), $limit);
    }

    /**
     * @param  array<string, mixed>  $report
     * @return array<int, array<int, int|string>>
     */
    private function buildDailyActivityRows(array $report): array
    {
        $dailyCreated = (array) ($report['dailyCreated'] ?? []);
        $dailyEngagement = (array) ($report['dailyEngagement'] ?? []);

        $labels = (array) ($dailyCreated['jav']['labels'] ?? []);
        $javValues = (array) ($dailyCreated['jav']['values'] ?? []);
        $actorValues = (array) ($dailyCreated['actors']['values'] ?? []);
        $tagValues = (array) ($dailyCreated['tags']['values'] ?? []);
        $favoriteValues = (array) ($dailyEngagement['favorites']['values'] ?? []);
        $watchlistValues = (array) ($dailyEngagement['watchlists']['values'] ?? []);
        $ratingValues = (array) ($dailyEngagement['ratings']['values'] ?? []);
        $historyValues = (array) ($dailyEngagement['history']['values'] ?? []);

        $rows = [];
        foreach ($labels as $index => $label) {
            $rows[] = [
                (string) $label,
                (int) ($javValues[$index] ?? 0),
                (int) ($actorValues[$index] ?? 0),
                (int) ($tagValues[$index] ?? 0),
                (int) ($favoriteValues[$index] ?? 0),
                (int) ($watchlistValues[$index] ?? 0),
                (int) ($ratingValues[$index] ?? 0),
                (int) ($historyValues[$index] ?? 0),
            ];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, mixed>>  $rows
     * @param  array<int, string>  $headers
     */
    private function renderTopTable(string $title, array $rows, array $headers, int $limit, callable $formatter): void
    {
        $this->info($title);
        $formatted = collect($rows)
            ->take($limit)
            ->map(static function (array $row) use ($formatter): array {
                return $formatter($row);
            })
            ->values()
            ->all();

        $this->table($headers, $formatted);
        $this->newLine();
    }

    /**
     * @param  array<string, mixed>  $telemetry
     */
    private function renderTelemetry(array $telemetry, int $limit): void
    {
        $this->info('Job Telemetry');

        if ((bool) ($telemetry['unavailable'] ?? false)) {
            $this->warn('Telemetry unavailable: '.(string) ($telemetry['error'] ?? 'unknown error'));

            return;
        }

        $overview = (array) ($telemetry['overview'] ?? []);
        $this->table(['Metric', 'Value'], [
            ['Total completed', (int) ($overview['total_completed'] ?? 0)],
            ['Success', (int) ($overview['success'] ?? 0)],
            ['Failed', (int) ($overview['failed'] ?? 0)],
            ['Success rate %', (float) ($overview['success_rate'] ?? 0.0)],
            ['Timeout count', (int) ($overview['timeout_count'] ?? 0)],
            ['Throughput / sec', (float) ($overview['throughput_per_sec'] ?? 0.0)],
            ['P50 ms', (int) ($overview['p50_ms'] ?? 0)],
            ['P95 ms', (int) ($overview['p95_ms'] ?? 0)],
            ['P99 ms', (int) ($overview['p99_ms'] ?? 0)],
        ]);
        $this->newLine();

        $performanceRows = collect((array) ($telemetry['job_performance'] ?? []))
            ->take($limit)
            ->map(static function (array $row): array {
                return [
                    (string) ($row['job_name'] ?? 'unknown'),
                    (int) ($row['total'] ?? 0),
                    (int) ($row['failed'] ?? 0),
                    (float) ($row['fail_rate'] ?? 0.0),
                    (int) ($row['p95_ms'] ?? 0),
                    (int) (($row['max_ms'] ?? 0) ?: 0),
                ];
            })
            ->all();

        $this->table(['Job', 'Total', 'Failed', 'Fail %', 'P95 ms', 'Max ms'], $performanceRows);
    }
}
