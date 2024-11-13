<?php

namespace Modules\Udemy\Services;

use Illuminate\Support\Str;
use Modules\Udemy\Exceptions\StudyClassTypeNotFound;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;

class StudyManager
{
    protected function detectType(CurriculumItem $curriculumItem): string
    {
        $class = $curriculumItem->detectType();

        $class = 'Modules\\Udemy\\Services\\Study\\' . (Str::ucfirst(Str::camel($class)));

        if (!class_exists($class)) {
            throw new StudyClassTypeNotFound("Class $class does not exist");
        }

        return $class;
    }

    public function study(
        UserToken $userToken,
        CurriculumItem $curriculumItem
    ): void {
        $class = $this->detectType($curriculumItem);

        app($class)->study($userToken, $curriculumItem);
    }
}
