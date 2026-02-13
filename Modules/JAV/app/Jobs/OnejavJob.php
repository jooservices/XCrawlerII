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
use Modules\JAV\Services\OnejavService;
use Throwable;

class OnejavJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $type
    ) {
    }

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
        $result = $service->{$this->type}();
        $items = $result->items();

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
}
