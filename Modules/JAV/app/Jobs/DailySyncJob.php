<?php

namespace Modules\JAV\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Modules\JAV\Services\FfjavService;
use Modules\JAV\Services\OneFourOneJavService;
use Modules\JAV\Services\OnejavService;
use Throwable;

class DailySyncJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 3600;

    public function __construct(
        public string $source,
        public ?string $date = null,
        public int $page = 1
    ) {}

    public function uniqueId(): string
    {
        return $this->source.':'.$this->resolvedDate().':'.$this->page;
    }

    public function handle(): void
    {
        $service = $this->resolveService();
        $result = $service->daily($this->resolvedDate(), $this->page);
        $items = $result->items();

        if ($items->hasNextPage) {
            $nextQueue = (is_string($this->queue) && $this->queue !== '')
                ? $this->queue
                : $this->defaultQueueForSource();

            Bus::dispatch((new self(
                source: $this->source,
                date: $this->resolvedDate(),
                page: $items->nextPage
            ))->onQueue($nextQueue));
        }
    }

    public function failed(Throwable $exception): void
    {
        Log::error('DailySyncJob failed', [
            'source' => $this->source,
            'date' => $this->resolvedDate(),
            'page' => $this->page,
            'error' => $exception->getMessage(),
        ]);
    }

    private function resolvedDate(): string
    {
        return $this->date
            ? Carbon::parse($this->date)->toDateString()
            : Carbon::now()->toDateString();
    }

    private function resolveService(): OnejavService|OneFourOneJavService|FfjavService
    {
        return match ($this->source) {
            'onejav' => app(OnejavService::class),
            '141jav' => app(OneFourOneJavService::class),
            'ffjav' => app(FfjavService::class),
            default => throw new \InvalidArgumentException("Unsupported source: {$this->source}"),
        };
    }

    public function backoff(): array
    {
        return [1800, 2700, 3600];
    }

    private function defaultQueueForSource(): string
    {
        return match ($this->source) {
            'onejav' => (string) config('jav.content_queues.onejav', 'onejav'),
            '141jav' => (string) config('jav.content_queues.141jav', '141'),
            default => (string) config('jav.content_queues.ffjav', 'jav'),
        };
    }
}
