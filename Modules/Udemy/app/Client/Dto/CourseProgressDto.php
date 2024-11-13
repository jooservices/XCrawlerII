<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\AbstractBaseDto;

class CourseProgressDto extends AbstractBaseDto
{

    public function getFields(): array
    {
        return [];
    }

    public function getCompletedIds(): array
    {
        return array_merge(
            $this->completed_lecture_ids,
            $this->completed_quiz_ids,
            $this->completed_assignment_ids
        );
    }
}
