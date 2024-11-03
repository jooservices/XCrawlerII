<?php

namespace Modules\Udemy\Tests\Unit\Services;

use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Modules\Udemy\Events\BeforeProcessCompleteCurriculumItemEvent;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\UdemyService;
use Modules\Udemy\Tests\TestCase;

class UdemyServiceTest extends TestCase
{
    /**
     * @throws Exception
     */
    public function testCompleteCurriculumSuccess(): void
    {
        Event::fake([
            BeforeProcessCompleteCurriculumItemEvent::class,
        ]);

        Bus::fake();

        /**
         * @TODO No wishing yet
         */
        $item = CurriculumItem::factory()->create([
            'class' => 'lecture',
        ]);

        $userToken = UserToken::factory()->create();

        $service = app(UdemyService::class);
        $service->completeCurriculum($userToken, $item);

        Event::assertDispatched(BeforeProcessCompleteCurriculumItemEvent::class);
    }
}
