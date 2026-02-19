<?php

namespace Modules\Core\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Core\Services\AnalyticsFlushService;

class FlushAnalyticsCountersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 10;

    public function handle(AnalyticsFlushService $flushService): void
    {
        $result = $flushService->flush();

        if (($result['errors'] ?? 0) > 0) {
            Log::warning('Analytics flush completed with errors', $result);
        }
    }
}
