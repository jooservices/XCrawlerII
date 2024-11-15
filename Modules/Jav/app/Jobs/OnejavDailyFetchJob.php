<?php

namespace Modules\Jav\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Modules\Core\Services\SettingService;
use Modules\Jav\Client\Onejav\CrawlingService;
use Modules\Jav\Events\OnejavDailyProcessedEvent;
use Modules\Jav\Repositories\OnejavRepository;
use Modules\Jav\Services\Onejav\OnejavService;
use Throwable;

class OnejavDailyFetchJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public string $date, public int $page = 1)
    {
        $this->onQueue(OnejavService::ONEJAV_QUEUE_NAME);
    }

    /**
     * Execute the job.
     */
    public function handle(CrawlingService $service): void
    {
        $items = $service->getItems($this->date, $this->page);
        $repository = app(OnejavRepository::class);

        foreach ($items as $item) {
            $repository->insert($item->toArray());
        }

        $totalPages = (int) app(SettingService::class)->get(
            OnejavService::SETTING_GROUP,
            Str::slug($this->date, '_') . '_last_page',
        );

        if ($totalPages > 1 && $this->page < $totalPages) {
            OnejavDailyFetchJob::dispatch($this->date, $this->page + 1);
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
