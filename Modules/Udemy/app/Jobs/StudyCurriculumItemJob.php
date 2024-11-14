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
use Modules\Udemy\Services\StudyService;
use Modules\Udemy\Services\UdemyService;

class StudyCurriculumItemJob implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public UserToken $userToken,
        public CurriculumItem $curriculumItem
    ) {
        $this->onQueue(UdemyService::UDEMY_QUEUE_NAME);
    }

    /**
     * Execute the job.
     */
    public function handle(StudyService $service): void
    {
        $service->studyCurriculum(
            $this->userToken,
            $this->curriculumItem
        );
    }
}
