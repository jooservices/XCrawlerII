<?php

namespace Modules\Udemy\Jobs;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\UdemyService;

class LectureProgressLogJob implements ShouldQueue
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
        public CurriculumItem $curriculumItem,
        public array $payload
    ) {
        $this->onQueue(UdemyService::UDEMY_QUEUE_NAME);
    }

    /**
     * Execute the job.
     * @throws \Exception
     */
    final public function handle(UdemySdk $udemySdk): void
    {
        $result = $udemySdk->setToken($this->userToken)
            ->me()
            ->lectureProgressLogs(
                $this->curriculumItem,
                $this->payload
            );

        $udemyCourse = $this->curriculumItem->course;

        Log::debug(
            'Course [' . $udemyCourse->id . ']: ' . $udemyCourse->title,
            [
                'result' => $result,
                'payload' => $this->payload,
            ]
        );

        if (!$result) {
            $this->fail();
        }
    }
}
