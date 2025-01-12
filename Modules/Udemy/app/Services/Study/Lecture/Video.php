<?php

namespace Modules\Udemy\Services\Study\Lecture;

use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Jobs\LectureProgressLogJob;
use Modules\Udemy\Services\Study\BaseStudyChild;
use Modules\Udemy\Services\UdemyService;
use Throwable;

class Video extends BaseStudyChild
{
    /**
     * @throws Throwable
     */
    final public function study(): void
    {
        $totalTime = $this->curriculumItem->asset_time_estimation;
        $parts = (int) ceil($totalTime / (15 * 5));

        $now = Carbon::now();
        $payloads = [];

        for ($index = 0; $index < $parts; $index++) {
            for ($part = 1; $part <= 5; $part++) {
                $time = ($index * 5 * 15) + (15 * $part);
                $payloads[$index][] = [
                    'context' => [
                        'type' => 'Lecture',
                    ],
                    'isFullscreen' => false,
                    'openPanel' => 'default',
                    'position' => $time,
                    'time' => $now->addSeconds($time)->format('Y-m-d\TH:i:s:v\Z'),
                    'total' => $totalTime,
                ];
            }
        }

        $chains = [];

        foreach ($payloads as $payload) {
            $chains[] = new LectureProgressLogJob(
                $this->userToken,
                $this->curriculumItem,
                $payload
            );
        }

        /**
         * Use chains to make sure we fully watch lecture than complete it
         */
        Bus::batch([$chains])
            ->then(function () {
                $this->sdk
                    ->me()
                    ->completedLectures($this->curriculumItem);
            })
            ->onQueue(UdemyService::UDEMY_QUEUE_NAME)
            ->dispatch();
    }
}
