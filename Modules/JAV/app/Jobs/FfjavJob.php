<?php

namespace Modules\JAV\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\JAV\Events\FfjavJobCompleted;
use Modules\JAV\Events\FfjavJobFailed;
use Modules\JAV\Exceptions\CrawlerDelayException;
use Modules\JAV\Services\FfjavService;
use Throwable;

class FfjavJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public int $timeout = 3600;

    public function __construct(
        public string $type
    ) {}

    public function uniqueId(): string
    {
        return $this->type;
    }

    public function handle(FfjavService $service): void
    {
        try {
            $result = $service->{$this->type}();
            $items = $result->items();
        } catch (CrawlerDelayException $exception) {
            $this->release($exception->delaySeconds());
            return;
        }

        FfjavJobCompleted::dispatch(
            $this->type,
            $items->items->count()
        );
    }

    public function failed(Throwable $exception): void
    {
        FfjavJobFailed::dispatch($this->type, $exception);
    }

    public function backoff(): array
    {
        return [1800, 2700, 3600];
    }
}
