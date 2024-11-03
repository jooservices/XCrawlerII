<?php

namespace Modules\Udemy\Tests\Unit\Services;

use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\UdemyService;
use Modules\Udemy\Tests\TestCase;

class UdemyServiceTest extends TestCase
{
    public function testCompleteCurriculum(): void
    {
        /**
         * @TODO No wishing yet
         */
        $item = CurriculumItem::factory()->create();
        $userToken = UserToken::factory()->create();

        $service = app(UdemyService::class);
        $service->completeCurriculum($userToken, $item);
    }
}
