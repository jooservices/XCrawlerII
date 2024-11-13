<?php

namespace Modules\Udemy\Services\Study;

use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Study\Interfaces\IStudy;
use Throwable;

class Article implements IStudy
{
    /**
     * @throws Throwable
     */
    public function study(
        UserToken $userToken,
        CurriculumItem $curriculumItem
    ): void {
        $me = app(UdemySdk::class)
            ->setToken($userToken)
            ->me();

        $me->viewLogs($curriculumItem);
        $me->lectureProgressLogs($curriculumItem);
        $me->completedLectures($curriculumItem);
    }
}
