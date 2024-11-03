<?php

namespace Modules\Udemy\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\UdemySdk;
use Modules\Udemy\Services\UdemyService;

/**
 * For lecture
 */
class CompleteCurriculumItemJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;
    use Batchable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public UserToken $userToken,
        public CurriculumItem $curriculumItem,
        public array $payload
    ) {
        $this->onQueue(UdemyService::UDEMY_QUEUE_NAME);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $result = app(UdemySdk::class)
            ->me()
            ->lectureProgressLogs(
                $this->userToken->token,
                $this->curriculumItem,
                $this->payload
            );

        if (!$result) {
            $this->fail();
        }
    }
}
