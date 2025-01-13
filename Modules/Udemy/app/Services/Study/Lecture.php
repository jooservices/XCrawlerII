<?php

namespace Modules\Udemy\Services\Study;

use Illuminate\Support\Str;
use Modules\Udemy\Services\StudyManager;

class Lecture extends BaseStudy
{
    final protected function getClass(): string
    {
        return StudyManager::NAMESPACE . 'Lecture\\'
            . Str::studly($this->curriculumItem->asset_type);
    }
}
