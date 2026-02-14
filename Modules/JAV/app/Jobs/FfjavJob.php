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
use Modules\JAV\Services\FfjavService;
use Throwable;

class FfjavJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $type
    ) {}

    public function uniqueId(): string
    {
        return $this->type;
    }

    public function handle(FfjavService $service): void
    {
        $result = $service->{$this->type}();
        $items = $result->items();

        FfjavJobCompleted::dispatch(
            $this->type,
            $items->items->count()
        );
    }

    public function failed(Throwable $exception): void
    {
        FfjavJobFailed::dispatch($this->type, $exception);
    }
}
