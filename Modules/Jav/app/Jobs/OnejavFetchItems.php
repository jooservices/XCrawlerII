<?php

namespace Modules\Jav\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Core\Services\SettingService;
use Modules\Jav\Models\OnejavReference;
use Modules\Jav\Services\OnejavCrawlingService;
use Throwable;

class OnejavFetchItems implements ShouldQueue
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
        public int $page
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(OnejavCrawlingService $service): void
    {
        $items = $service->getItems($this->endpoint, $this->page);
        app(SettingService::class)
            ->set(
                'onejav',
                $this->endpoint . '_current_page',
                $this->page + 1
            );

        foreach ($items as $item) {
            OnejavReference::updateOrCreate([
                'url' => $item->url,
                'dvd_id' => $item->dvd_id,
            ], $item->toArray());
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
