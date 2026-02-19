<?php

namespace Modules\JAV\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Facades\Config;
use Modules\JAV\Http\Requests\RequestSyncRequest;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\FfjavJob;
use Modules\JAV\Jobs\OneFourOneJavJob;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Jobs\TagsSyncJob;
use Modules\JAV\Jobs\XcityKanaSyncJob;
use Modules\JAV\Services\XcityIdolService;

class SyncController extends Controller
{
    public function dispatch(RequestSyncRequest $request, XcityIdolService $xcityIdolService): JsonResponse
    {
        $source = $request->source;
        $type = $request->type;
        $date = $request->validated('date');

        $lockKey = sprintf('jav:sync:dispatch:%s:%s', $source, $type);
        if (! Cache::add($lockKey, 1, now()->addSeconds(20))) {
            return response()->json([
                'message' => 'A sync request for this provider/type is already being dispatched. Please wait a moment.',
            ], 429);
        }

        Cache::put('jav:sync:active', [
            'provider' => $source,
            'type' => $type,
            'started_at' => now()->toIso8601String(),
        ], now()->addHours(6));

        $dispatched = match ($type) {
            'new' => match ($source) {
                'onejav' => Bus::dispatch((new OnejavJob('new'))->onQueue((string) config('jav.content_queues.onejav', 'onejav'))),
                '141jav' => Bus::dispatch((new OneFourOneJavJob('new'))->onQueue((string) config('jav.content_queues.141jav', '141'))),
                'ffjav' => Bus::dispatch((new FfjavJob('new'))->onQueue('jav')),
            },
            'popular' => match ($source) {
                'onejav' => Bus::dispatch((new OnejavJob('popular'))->onQueue((string) config('jav.content_queues.onejav', 'onejav'))),
                '141jav' => Bus::dispatch((new OneFourOneJavJob('popular'))->onQueue((string) config('jav.content_queues.141jav', '141'))),
                'ffjav' => Bus::dispatch((new FfjavJob('popular'))->onQueue('jav')),
            },
            'daily' => Bus::dispatch((new DailySyncJob(
                $source,
                is_string($date) && $date !== '' ? Carbon::parse($date)->toDateString() : now()->toDateString(),
                1
            ))->onQueue(match ($source) {
                'onejav' => (string) config('jav.content_queues.onejav', 'onejav'),
                '141jav' => (string) config('jav.content_queues.141jav', '141'),
                default => (string) config('jav.content_queues.ffjav', 'jav'),
            })),
            'tags' => Bus::dispatch((new TagsSyncJob($source))->onQueue(match ($source) {
                'onejav' => (string) config('jav.content_queues.onejav', 'onejav'),
                '141jav' => (string) config('jav.content_queues.141jav', '141'),
                default => (string) config('jav.content_queues.ffjav', 'jav'),
            })),
            'idols' => $this->dispatchXcityIdolJobs($xcityIdolService),
        };

        return response()->json([
            'message' => $type === 'idols'
                ? "XCITY idol sync queued ({$dispatched} jobs)."
                : 'Provider sync request queued successfully.',
            'source' => $source,
            'type' => $type,
            'date' => $type === 'daily' && is_string($date) && $date !== '' ? Carbon::parse($date)->toDateString() : null,
            'jobs' => $type === 'idols' ? $dispatched : null,
        ]);
    }

    public function providerSyncStatus(): JsonResponse
    {
        $progress = $this->buildSyncProgressSnapshot();

        return response()->json([
            'onejav' => [
                'new' => Config::get('onejav', 'new_page', 1),
                'popular' => Config::get('onejav', 'popular_page', 1),
            ],
            '141jav' => [
                'new' => Config::get('onefourone', 'new_page', 1),
                'popular' => Config::get('onefourone', 'popular_page', 1),
            ],
            'ffjav' => [
                'new' => Config::get('ffjav', 'new_page', 1),
                'popular' => Config::get('ffjav', 'popular_page', 1),
            ],
            'progress' => $progress,
        ]);
    }

    public function syncProgressData(): JsonResponse
    {
        return response()->json($this->buildSyncProgressSnapshot());
    }

    /**
     * Build a lightweight realtime sync snapshot from queue + cache state.
     *
     * @return array<string, mixed>
     */
    private function buildSyncProgressSnapshot(): array
    {
        $jobsTableExists = Schema::hasTable('jobs');
        $failedJobsTableExists = Schema::hasTable('failed_jobs');

        $trackedQueues = ['jav', 'onejav', '141', (string) config('jav.idol_queue', 'xcity')];

        $pendingJobs = $jobsTableExists
            ? DB::table('jobs')->whereIn('queue', $trackedQueues)->count()
            : 0;

        $failedJobs = $failedJobsTableExists
            ? DB::table('failed_jobs')
                ->whereIn('queue', $trackedQueues)
                ->where('failed_at', '>=', now()->subDay())
                ->count()
            : 0;

        $activeSync = Cache::get('jav:sync:active');

        $phase = 'idle';
        if ($pendingJobs > 0) {
            $phase = 'processing';
        } elseif (is_array($activeSync)) {
            $phase = 'completed';
        }

        $metrics = Cache::get('jav:sync:metrics', []);
        $now = now();
        $currentTs = $now->timestamp;
        $ratePerMinute = null;
        $etaSeconds = null;

        if (($metrics['last_ts'] ?? null) && array_key_exists('last_pending', $metrics)) {
            $elapsed = max(1, $currentTs - (int) $metrics['last_ts']);
            $delta = (int) $metrics['last_pending'] - $pendingJobs;

            if ($delta > 0) {
                $instantRate = ($delta / $elapsed) * 60;
                $previousRate = isset($metrics['rate_per_min']) ? (float) $metrics['rate_per_min'] : $instantRate;
                $ratePerMinute = round(($previousRate * 0.6) + ($instantRate * 0.4), 2);
            } elseif (isset($metrics['rate_per_min'])) {
                $ratePerMinute = (float) $metrics['rate_per_min'];
            }
        }

        if ($ratePerMinute && $ratePerMinute > 0 && $pendingJobs > 0) {
            $etaSeconds = (int) round(($pendingJobs / $ratePerMinute) * 60);
        }

        Cache::put('jav:sync:metrics', [
            'last_pending' => $pendingJobs,
            'last_ts' => $currentTs,
            'rate_per_min' => $ratePerMinute,
        ], now()->addHours(6));

        $recentFailures = $failedJobsTableExists
            ? DB::table('failed_jobs')
                ->whereIn('queue', $trackedQueues)
                ->orderByDesc('failed_at')
                ->limit(5)
                ->get(['id', 'failed_at', 'exception'])
                ->map(static function (object $failure): array {
                    $message = 'Unknown error';
                    if (is_string($failure->exception) && $failure->exception !== '') {
                        $firstLine = explode("\n", $failure->exception)[0] ?? '';
                        $message = mb_strimwidth(trim($firstLine), 0, 180, '...');
                    }

                    return [
                        'id' => $failure->id,
                        'failed_at' => Carbon::parse($failure->failed_at)->toDateTimeString(),
                        'message' => $message,
                    ];
                })
                ->values()
                ->all()
            : [];

        return [
            'phase' => $phase,
            'pending_jobs' => $pendingJobs,
            'failed_jobs_24h' => $failedJobs,
            'throughput_per_min' => $ratePerMinute,
            'eta_seconds' => $etaSeconds,
            'eta_human' => $etaSeconds ? gmdate('H:i:s', $etaSeconds) : null,
            'active_sync' => $activeSync,
            'recent_failures' => $recentFailures,
            'updated_at' => $now->toDateTimeString(),
        ];
    }

    private function dispatchXcityIdolJobs(XcityIdolService $xcityIdolService): int
    {
        $idolQueue = (string) config('jav.idol_queue', 'xcity');

        $seeds = $xcityIdolService->seedKanaUrls();
        if ($seeds === []) {
            return 0;
        }

        $selected = $xcityIdolService->pickSeedsForDispatch($seeds, 2);
        foreach ($selected as $seed) {
            Bus::dispatch((new XcityKanaSyncJob($seed['seed_key'], $seed['seed_url']))->onQueue($idolQueue));
        }

        return $selected->count();
    }
}
