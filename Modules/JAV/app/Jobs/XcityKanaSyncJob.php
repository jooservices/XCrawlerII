<?php

namespace Modules\JAV\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\JAV\Services\XcityIdolService;
use Throwable;

class XcityKanaSyncJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(
        public string $seedKey,
        public string $seedUrl
    ) {}

    public function uniqueId(): string
    {
        return 'xcity:'.$this->seedKey;
    }

    public function handle(XcityIdolService $service): void
    {
        $service->syncKanaPage($this->seedKey, $this->seedUrl, $this->queue);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('XcityKanaSyncJob failed', [
            'seed_key' => $this->seedKey,
            'seed_url' => $this->seedUrl,
            'error' => $exception->getMessage(),
        ]);
    }
}
