<?php

namespace Modules\Udemy\Repositories;

use Modules\Udemy\Client\Dto\CourseCurriculumItemDto;
use Modules\Udemy\Client\Dto\CourseDto;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UdemyCourse;

class CourseRepository
{
    final public function createFromEntity(CourseDto $courseDto): UdemyCourse
    {
        return $this->createCourse($courseDto->toArray());
    }

    final public function createFromId(int $courseId): UdemyCourse
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

    final public function syncCurriculumItem(
        UdemyCourse $udemyCourse,
        CourseCurriculumItemDto $itemDto
    ): CurriculumItem {

        if (CurriculumItem::where('id', $itemDto->id)->exists()) {
            return CurriculumItem::where('id', $itemDto->id)->first();
        }

        return $udemyCourse->items()->updateOrCreate(
            [
                'id' => $itemDto->id,
            ],
            $itemDto->toArray()
        );
    }
}
