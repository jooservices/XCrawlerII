<?php

namespace Modules\Jav\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Facades\Setting;
use Modules\Jav\Client\Onejav\CrawlingService;
use Modules\Jav\Repositories\OnejavRepository;
use Modules\Jav\Services\Onejav\OnejavService;
use Throwable;

class OnejavFetchItemsJob implements ShouldQueue
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
        public int $page = 1
    ) {
        $this->onQueue(OnejavService::ONEJAV_QUEUE_NAME);
    }

    /**
     * Execute the job.
     */
    public function handle(CrawlingService $service): void
    {
        $items = $service->getItems($this->endpoint, $this->page);

        $lastPage = (int) Setting::get(
            'onejav',
            $this->endpoint . '_last_page',
            1
        );

        if ($lastPage === $this->page) {
            $this->page = 0;
            /**
             * Dispatch event
             */
        }

        Setting::set(
            'onejav',
            $this->endpoint . '_current_page',
            $this->page + 1
        );

        $repository = app(OnejavRepository::class);

        foreach ($items as $item) {
            $repository->insert($item->toArray());
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        /**
         * @TODO Should we dispatch another
         */
    }
}
