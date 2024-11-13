<?php

namespace Modules\Udemy\Services\Study\Interfaces;

use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;

interface IStudy
{
    public function study(
        UserToken $userToken,
        CurriculumItem $curriculumItem
    ): void;
}
