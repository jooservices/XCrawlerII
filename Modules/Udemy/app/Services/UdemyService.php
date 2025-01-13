<?php

namespace Modules\Udemy\Services;

use Modules\Udemy\Services\Traits\TCourseCurriculums;
use Modules\Udemy\Services\Traits\TCourses;

class UdemyService
{
    use TCourseCurriculums;
    use TCourses;

    public const string UDEMY_QUEUE_NAME = 'udemy';
}
