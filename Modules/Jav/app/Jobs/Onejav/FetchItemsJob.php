<?php

namespace Modules\Jav\Jobs\Onejav;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Jav\Services\Onejav\OnejavService;

class FetchItemsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $endpoint,
        public int $page = 1,
        public bool $loop = false
    ) {
        $this->onQueue(OnejavService::ONEJAV_QUEUE_NAME);
    }

    /**
     * Execute the job.
     */
    public function handle(OnejavService $service): void
    {
        $items = $service->crawl($this->endpoint, $this->page);

        if ($this->loop && !$items->isLastPage()) {
            self::dispatch($this->endpoint, $this->page + 1);
        }
    }
}
