<?php

namespace Modules\Udemy\Client\Dto;

use Modules\Core\Dto\BaseDto;

/**
 * @property array $completed_lecture_ids
 * @property array $completed_quiz_ids
 * @property array $completed_assignment_ids
 */
class CourseProgressDto extends BaseDto
{
    public function getCompletedIds(): array
    {
        return array_merge(
            $this->completed_lecture_ids,
            $this->completed_quiz_ids,
            $this->completed_assignment_ids
        );
    }
}
