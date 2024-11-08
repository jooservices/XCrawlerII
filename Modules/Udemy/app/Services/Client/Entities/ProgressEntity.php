<?php

namespace Modules\Udemy\Services\Client\Entities;

class ProgressEntity extends AbstractBaseEntity
{

    public function getCompletedLectureIds(): array
    {
        return array_merge(
            $this->data->completed_assignment_ids,
            $this->data->completed_lecture_ids,
            $this->data->completed_quiz_ids,
        );
    }
}
