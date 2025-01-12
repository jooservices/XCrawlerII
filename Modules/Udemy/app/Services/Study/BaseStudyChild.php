<?php

namespace Modules\Udemy\Services\Study;

use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Study\Interfaces\IStudy;

abstract class BaseStudyChild implements IStudy
{
    protected UdemySdk $sdk;

    public function __construct(
        protected UserToken $userToken,
        protected CurriculumItem $curriculumItem
    ) {
        $this->sdk = app(UdemySdk::class)
            ->setToken($this->userToken);
    }
}
