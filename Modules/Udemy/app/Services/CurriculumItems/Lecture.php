<?php

namespace Modules\Udemy\Services\CurriculumItems;

use Carbon\Carbon;
use Illuminate\Support\Facades\Bus;
use Modules\Udemy\Interfaces\IStudyCurriculum;
use Modules\Udemy\Jobs\CompleteCurriculumItemJob;
use Modules\Udemy\Jobs\CompleteLectureJob;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;

class Lecture implements IStudyCurriculum
{
    public function process(
        UserToken $userToken,
        CurriculumItem $curriculumItem
    ) {
        $totalTime = $curriculumItem->asset_time_estimation;
        $parts = (int)ceil($totalTime / (15 * 5));

        $now = Carbon::now();

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
            $chains[] = new CompleteCurriculumItemJob($userToken, $curriculumItem, $payload);
        }

        Bus::batch([$chains])
            ->then(function () use ($userToken, $curriculumItem) {
                CompleteLectureJob::dispatch($userToken, $curriculumItem);
            })
            ->dispatch();
    }
}
