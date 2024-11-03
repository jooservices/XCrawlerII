<?php

namespace Modules\Udemy\Interfaces;

use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;

interface IStudyCurriculum
{
    public function process(
        UserToken $userToken,
        CurriculumItem $curriculumItem
    );
}
