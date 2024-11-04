<?php

namespace Modules\Udemy\Repositories;

use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Services\Client\Entities\CourseEntity;

class UdemyCourseRepository
{
    public function create(CourseEntity $courseEntity): UdemyCourse
    {
        return UdemyCourse::updateOrCreate(
            [
                'id' => $courseEntity->getId(),
                'url' => $courseEntity->getUrl(),
            ],
            $courseEntity->toArray()
        );
    }
}
