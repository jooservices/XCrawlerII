<?php

namespace Modules\JAV\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\JAV\Events\OnejavJobCompleted;
use Modules\JAV\Events\OnejavJobFailed;
use Modules\JAV\Exceptions\CrawlerDelayException;
use Modules\JAV\Services\OnejavService;
use Throwable;

class OnejavJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 3600;

    public function __construct(
        public string $type
    ) {}

    /**
     * Get the unique ID for the job.
     */
    public function uniqueId(): string
    {
        return $this->type;
    }

    /**
     * Execute the job.
     */
    public function handle(OnejavService $service): void
    {
        try {
            $result = $service->{$this->type}();
            $items = $result->items();
        } catch (CrawlerDelayException $exception) {
            $this->release($exception->delaySeconds());
            return;
        }

        OnejavJobCompleted::dispatch(
            $this->type,
            $items->items->count()
        );
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        OnejavJobFailed::dispatch($this->type, $exception);
    }

    public function backoff(): array
    {
        return [1800, 2700, 3600];
    }
}
