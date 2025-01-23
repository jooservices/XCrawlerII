<?php

namespace Modules\Udemy\Services\Study\Lecture;

use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Modules\Udemy\Events\StudyCurriculumItemCompletedEvent;
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

        $udemyCourse = $this->curriculumItem->course;

        Log::debug(
            'Course [' . $udemyCourse->id . ']: ' . $udemyCourse->title,
            [
                'total_times' => $totalTime,
                'parts' => $parts,
            ]
        );

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

        if (empty($payloads)) {
            return;
        }

        $chains = [];

        foreach ($payloads as $payload) {
            $chains[] = new LectureProgressLogJob(
                $this->userToken,
                $this->curriculumItem,
                $payload
            );
        }

        Log::debug(
            'Course [' . $udemyCourse->id . ']: ' . $udemyCourse->title,
            [
                'chains' => count($chains),
            ]
        );

        /**
         * Use chains to make sure we fully watch lecture than complete it
         */
        $userToken = $this->userToken;
        $curriculumItem = $this->curriculumItem;

        Bus::batch([$chains])
            ->then(function () use ($userToken, $curriculumItem) {
                StudyCurriculumItemCompletedEvent::dispatch(
                    $userToken,
                    $curriculumItem
                );
            })
            ->onQueue(UdemyService::UDEMY_QUEUE_NAME)
            ->dispatch();
    }
}
