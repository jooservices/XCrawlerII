<?php

namespace Modules\Udemy\Services\Study;

use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Jobs\LectureProgressLogJob;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Study\Interfaces\IStudy;
use Modules\Udemy\Services\UdemyService;
use Throwable;

class Video implements IStudy
{
    /**
     * @throws Throwable
     */
    public function study(
        UserToken $userToken,
        CurriculumItem $curriculumItem
    ): void {
        $totalTime = $curriculumItem->asset_time_estimation;
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
            $chains[] = new LectureProgressLogJob($userToken, $curriculumItem, $payload);
        }

        /**
         * Use chains to make sure we fully watch lecture than complete it
         */
        Bus::batch([$chains])
            ->then(function () use ($userToken, $curriculumItem) {
                app(UdemySdk::class)
                    ->setToken($userToken)
                    ->me()
                    ->completedLectures($curriculumItem);
            })
            ->onQueue(UdemyService::UDEMY_QUEUE_NAME)
            ->dispatch();
    }
}
