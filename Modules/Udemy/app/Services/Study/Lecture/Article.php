<?php

namespace Modules\Udemy\Services\Study\Lecture;

use Modules\Udemy\Services\Study\BaseStudyChild;
use Throwable;

class Article extends BaseStudyChild
{
    /**
     * @throws Throwable
     */
    final public function study(): void
    {
        $me = $this->sdk->me();

        $me->viewLogs($this->curriculumItem);
        $me->lectureProgressLogs($this->curriculumItem);
        $me->completedLectures($this->curriculumItem);
    }
}
