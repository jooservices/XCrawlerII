<?php

namespace Modules\JAV\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Core\Facades\Config;
use Modules\JAV\Http\Requests\RequestSyncRequest;
use Modules\JAV\Jobs\DailySyncJob;
use Modules\JAV\Jobs\FfjavJob;
use Modules\JAV\Jobs\OneFourOneJavJob;
use Modules\JAV\Jobs\OnejavJob;
use Modules\JAV\Services\FfjavService;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Services\OnejavService;

class SyncController extends Controller
{
    public function dispatch(RequestSyncRequest $request): JsonResponse
    {
        $source = $request->source;
        $type = $request->type;
        $date = $request->validated('date');

        $lockKey = sprintf('jav:sync:dispatch:%s:%s', $source, $type);
        if (!Cache::add($lockKey, 1, now()->addSeconds(20))) {
            return response()->json([
                'message' => 'A sync request for this provider/type is already being dispatched. Please wait a moment.',
            ], 429);
        }

        Cache::put('jav:sync:active', [
            'provider' => $source,
            'type' => $type,
            'started_at' => now()->toIso8601String(),
        ], now()->addHours(6));

        match ($type) {
            'new' => match ($source) {
                'onejav' => OnejavJob::dispatch('new')->onQueue('jav'),
                '141jav' => OneFourOneJavJob::dispatch('new')->onQueue('jav'),
                'ffjav' => FfjavJob::dispatch('new')->onQueue('jav'),
            },
            'popular' => match ($source) {
                'onejav' => OnejavJob::dispatch('popular')->onQueue('jav'),
                '141jav' => OneFourOneJavJob::dispatch('popular')->onQueue('jav'),
                'ffjav' => FfjavJob::dispatch('popular')->onQueue('jav'),
            },
            'daily' => DailySyncJob::dispatch(
                $source,
                is_string($date) && $date !== '' ? Carbon::parse($date)->toDateString() : now()->toDateString(),
                1
            )->onQueue('jav'),
            'tags' => match ($source) {
                'onejav' => app(OnejavService::class)->tags(),
                '141jav' => app(OneFourOneJavService::class)->tags(),
                'ffjav' => app(FfjavService::class)->tags(),
            },
        };

        return response()->json([
            'message' => 'Provider sync request queued successfully.',
            'source' => $source,
            'type' => $type,
            'date' => $type === 'daily' && is_string($date) && $date !== '' ? Carbon::parse($date)->toDateString() : null,
        ]);
    }

    public function request(RequestSyncRequest $request): JsonResponse
    {
        Cache::put('jav:sync:active', [
            'provider' => $request->source,
            'type' => $request->type,
            'started_at' => now()->toIso8601String(),
        ], now()->addHours(6));

        match ($request->type) {
            'new' => match ($request->source) {
                'onejav' => OnejavJob::dispatch('new')->onQueue('jav'),
                '141jav' => OneFourOneJavJob::dispatch('new')->onQueue('jav'),
                'ffjav' => FfjavJob::dispatch('new')->onQueue('jav'),
            },
            'popular' => match ($request->source) {
                'onejav' => OnejavJob::dispatch('popular')->onQueue('jav'),
                '141jav' => OneFourOneJavJob::dispatch('popular')->onQueue('jav'),
                'ffjav' => FfjavJob::dispatch('popular')->onQueue('jav'),
            },
            'daily' => DailySyncJob::dispatch($request->source, now()->toDateString(), 1)->onQueue('jav'),
            'tags' => match ($request->source) {
                'onejav' => app(OnejavService::class)->tags(),
                '141jav' => app(OneFourOneJavService::class)->tags(),
                'ffjav' => app(FfjavService::class)->tags(),
            },
        };

        return response()->json([
            'message' => 'Sync request queued successfully.',
            'progress' => $this->buildSyncProgressSnapshot(),
        ]);
    }

    public function status(): JsonResponse
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

        $pendingJobs = $jobsTableExists
            ? DB::table('jobs')->where('queue', 'jav')->count()
            : 0;

        $failedJobs = $failedJobsTableExists
            ? DB::table('failed_jobs')
                ->where('queue', 'jav')
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
                ->where('queue', 'jav')
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
}
