<?php

namespace Modules\JAV\Services;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Core\Models\Mongo\JobTelemetryEvent;

class JobTelemetryAnalyticsService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function summary(array $filters): array
    {
        $windowMinutes = max(5, min(1440, (int) ($filters['window_minutes'] ?? 60)));
        $limit = max(5, min(200, (int) ($filters['limit'] ?? 30)));
        $site = $this->normalizeNullableString($filters['site'] ?? null);
        $jobName = $this->normalizeNullableString($filters['job_name'] ?? null);

        $to = CarbonImmutable::now('UTC');
        $from = $to->subMinutes($windowMinutes);

        $completed = $this->baseCompletedQuery($from, $to, $site, $jobName)
            ->orderBy('timestamp')
            ->limit(50000)
            ->get(['timestamp', 'site', 'job_name', 'status', 'duration_ms', 'error_class', 'timeout_ms_observed']);

        $started = $this->baseStartedQuery($from, $to, $site, $jobName)
            ->orderBy('timestamp')
            ->limit(50000)
            ->get(['timestamp', 'site']);

        $rateAlerts = JobTelemetryEvent::query()
            ->where('event_type', 'rate_limit_exceeded')
            ->whereBetween('timestamp', [$from, $to])
            ->when($site !== null, static fn (Builder $query) => $query->where('site', $site))
            ->orderBy('timestamp')
            ->limit(10000)
            ->get(['timestamp', 'site', 'status', 'jobs_per_second', 'warning_threshold', 'critical_threshold']);

        $overview = $this->buildOverview($completed, $windowMinutes);

        $jobPerformance = $this->buildJobPerformance($completed, $limit);
        $throughput = $this->buildThroughput($started, $rateAlerts, $windowMinutes);

        $failures = $this->baseCompletedQuery($from, $to, $site, $jobName)
            ->where('status', 'failed')
            ->orderByDesc('timestamp')
            ->limit($limit)
            ->get(['timestamp', 'site', 'job_name', 'error_class', 'error_code', 'error_message_short', 'timeout_ms_observed', 'duration_ms', 'attempt']);

        $slowJobs = $this->baseCompletedQuery($from, $to, $site, $jobName)
            ->whereNotNull('duration_ms')
            ->orderByDesc('duration_ms')
            ->limit($limit)
            ->get(['timestamp', 'site', 'job_name', 'status', 'duration_ms', 'error_class']);

        $availableSites = $completed
            ->pluck('site')
            ->merge($started->pluck('site'))
            ->filter(static fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $availableJobs = $completed
            ->pluck('job_name')
            ->filter(static fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        return [
            'filters' => [
                'window_minutes' => $windowMinutes,
                'site' => $site,
                'job_name' => $jobName,
                'limit' => $limit,
            ],
            'window' => [
                'from' => $from->toDateTimeString(),
                'to' => $to->toDateTimeString(),
            ],
            'overview' => $overview,
            'job_performance' => $jobPerformance,
            'throughput' => $throughput,
            'failures' => $failures->values()->all(),
            'slow_jobs' => $slowJobs->values()->all(),
            'available_sites' => $availableSites,
            'available_jobs' => $availableJobs,
            'generated_at' => $to->toDateTimeString(),
        ];
    }

    private function baseCompletedQuery(CarbonImmutable $from, CarbonImmutable $to, ?string $site, ?string $jobName): Builder
    {
        return JobTelemetryEvent::query()
            ->where('event_type', 'completed')
            ->whereBetween('timestamp', [$from, $to])
            ->when($site !== null, static fn (Builder $query) => $query->where('site', $site))
            ->when($jobName !== null, static fn (Builder $query) => $query->where('job_name', $jobName));
    }

    private function baseStartedQuery(CarbonImmutable $from, CarbonImmutable $to, ?string $site, ?string $jobName): Builder
    {
        return JobTelemetryEvent::query()
            ->where('event_type', 'started')
            ->whereBetween('timestamp', [$from, $to])
            ->when($site !== null, static fn (Builder $query) => $query->where('site', $site))
            ->when($jobName !== null, static fn (Builder $query) => $query->where('job_name', $jobName));
    }

    /**
     * @param  Collection<int, JobTelemetryEvent>  $completed
     * @return array<string, mixed>
     */
    private function buildOverview(Collection $completed, int $windowMinutes): array
    {
        $total = $completed->count();
        $failed = $completed->where('status', 'failed')->count();
        $success = $completed->where('status', 'success')->count();
        $timeouts = $completed
            ->filter(static fn ($row) => $row->timeout_ms_observed !== null || str_contains((string) ($row->error_class ?? ''), 'ConnectException'))
            ->count();

        $durations = $completed
            ->pluck('duration_ms')
            ->filter(static fn ($value) => is_numeric($value))
            ->map(static fn ($value) => (int) $value)
            ->sort()
            ->values();

        $seconds = max(1, $windowMinutes * 60);

        return [
            'total_completed' => $total,
            'success' => $success,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($success / $total) * 100, 2) : 0.0,
            'fail_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0.0,
            'timeout_count' => $timeouts,
            'timeout_rate' => $total > 0 ? round(($timeouts / $total) * 100, 2) : 0.0,
            'throughput_per_sec' => round($total / $seconds, 3),
            'p50_ms' => $this->percentile($durations, 0.50),
            'p95_ms' => $this->percentile($durations, 0.95),
            'p99_ms' => $this->percentile($durations, 0.99),
        ];
    }

    /**
     * @param  Collection<int, JobTelemetryEvent>  $completed
     * @return array<int, array<string, mixed>>
     */
    private function buildJobPerformance(Collection $completed, int $limit): array
    {
        return $completed
            ->groupBy(static fn ($row) => (string) ($row->job_name ?? 'unknown'))
            ->map(function (Collection $rows, string $name): array {
                $total = $rows->count();
                $failed = $rows->where('status', 'failed')->count();
                $durations = $rows->pluck('duration_ms')
                    ->filter(static fn ($value) => is_numeric($value))
                    ->map(static fn ($value) => (int) $value)
                    ->sort()
                    ->values();

                return [
                    'job_name' => $name,
                    'total' => $total,
                    'failed' => $failed,
                    'fail_rate' => $total > 0 ? round(($failed / $total) * 100, 2) : 0.0,
                    'p50_ms' => $this->percentile($durations, 0.50),
                    'p95_ms' => $this->percentile($durations, 0.95),
                    'p99_ms' => $this->percentile($durations, 0.99),
                    'max_ms' => $durations->isNotEmpty() ? (int) $durations->last() : null,
                ];
            })
            ->sortByDesc('total')
            ->take($limit)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, JobTelemetryEvent>  $started
     * @param  Collection<int, JobTelemetryEvent>  $rateAlerts
     * @return array<string, mixed>
     */
    private function buildThroughput(Collection $started, Collection $rateAlerts, int $windowMinutes): array
    {
        $bucketFormat = $windowMinutes <= 180 ? 'Y-m-d H:i' : 'Y-m-d H';

        $labels = $started
            ->pluck('timestamp')
            ->map(static fn ($value) => $value?->format($bucketFormat))
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $sites = $started
            ->pluck('site')
            ->filter(static fn ($value) => is_string($value) && $value !== '')
            ->unique()
            ->values();

        $series = $sites->map(function (string $site) use ($started, $labels, $bucketFormat): array {
            $siteRows = $started->filter(static fn ($row) => $row->site === $site);

            $points = $labels->map(function (string $label) use ($siteRows, $bucketFormat): int {
                return $siteRows
                    ->filter(static fn ($row) => $row->timestamp?->format($bucketFormat) === $label)
                    ->count();
            })->values()->all();

            return [
                'site' => $site,
                'points' => $points,
                'total_started' => array_sum($points),
            ];
        })->sortByDesc('total_started')->take(8)->values()->all();

        $alerts = $rateAlerts
            ->map(static function ($row): array {
                return [
                    'timestamp' => $row->timestamp,
                    'site' => $row->site,
                    'status' => $row->status,
                    'jobs_per_second' => (int) ($row->jobs_per_second ?? 0),
                    'warning_threshold' => (int) ($row->warning_threshold ?? 0),
                    'critical_threshold' => (int) ($row->critical_threshold ?? 0),
                ];
            })
            ->sortByDesc('timestamp')
            ->values()
            ->all();

        $peakBySite = collect($alerts)
            ->groupBy('site')
            ->map(static fn (Collection $rows) => $rows->max('jobs_per_second'))
            ->sortDesc()
            ->take(8)
            ->all();

        return [
            'labels' => $labels->all(),
            'series' => $series,
            'alerts' => $alerts,
            'peak_by_site' => $peakBySite,
        ];
    }

    /**
     * @param  Collection<int, int>  $values
     */
    private function percentile(Collection $values, float $percentile): ?int
    {
        $count = $values->count();
        if ($count === 0) {
            return null;
        }

        $index = (int) ceil($percentile * $count) - 1;
        $index = max(0, min($count - 1, $index));

        return (int) $values->get($index);
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
