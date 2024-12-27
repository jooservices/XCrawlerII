<?php

namespace Modules\Udemy\Services;

use Illuminate\Support\Str;
use Modules\Udemy\Exceptions\StudyClassTypeNotFound;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;

class StudyManager
{
    private const string NAMESPACE = 'Modules\\Udemy\\Services\\Study\\';

    private function detectType(CurriculumItem $curriculumItem): string
    {
        $class = self::NAMESPACE . (Str::ucfirst(Str::camel(
            $curriculumItem->detectType()
        )));

        if (!class_exists($class)) {
            throw new StudyClassTypeNotFound("Class $class does not exist");
        }

        return $class;
    }

    final public function study(
        UserToken $userToken,
        CurriculumItem $curriculumItem
    ): void {
        $class = $this->detectType($curriculumItem);
        app($class)->study($userToken, $curriculumItem);
    }
}
