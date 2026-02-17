<?php

namespace Modules\JAV\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\JAV\Services\FfjavService;
use Modules\JAV\Services\MissavService;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Services\OnejavService;
use Throwable;

class TagsSyncJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 3600;

    public function __construct(
        public string $source
    ) {}

    public function uniqueId(): string
    {
        return $this->source;
    }

    public function handle(): void
    {
        $service = $this->resolveService();
        $service->tags();
    }

    public function failed(Throwable $exception): void
    {
        Log::error('TagsSyncJob failed', [
            'source' => $this->source,
            'error' => $exception->getMessage(),
        ]);
    }

    public function backoff(): array
    {
        return [1800, 2700, 3600];
    }

    private function resolveService(): OnejavService|OneFourOneJavService|FfjavService|MissavService
    {
        return match ($this->source) {
            'onejav' => app(OnejavService::class),
            '141jav' => app(OneFourOneJavService::class),
            'ffjav' => app(FfjavService::class),
            'missav' => app(MissavService::class),
            default => throw new \InvalidArgumentException("Unsupported source: {$this->source}"),
        };
    }
}
