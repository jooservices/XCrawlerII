<?php

namespace Modules\Udemy\Repositories;

use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Services\Client\Entities\CourseEntity;

class UdemyCourseRepository
{
    public function createFromEntity(CourseEntity $courseEntity): UdemyCourse
    {
        return $this->createCourse($courseEntity->toArray());
    }

    public function createFromId(int $courseId): UdemyCourse
    {
        return $this->createCourse([
            'id' => $courseId,
            'class' => 'course',
        ]);
    }

    private function createCourse(array $data): UdemyCourse
    {
        $model = UdemyCourse::updateOrCreate([
            'id' => $data['id'],
        ], $data);

        /**
         * @TODO Dispatch event and fetch data if needed
         */

        return $model;
    }
}
