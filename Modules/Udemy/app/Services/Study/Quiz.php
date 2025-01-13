<?php

namespace Modules\Udemy\Services\Study;

use Illuminate\Support\Str;
use Modules\Udemy\Services\StudyManager;

class Quiz extends BaseStudy
{
    final protected function getClass(): string
    {
        return StudyManager::NAMESPACE . 'Quiz\\'
            . Str::studly($this->curriculumItem->type);
    }
}
