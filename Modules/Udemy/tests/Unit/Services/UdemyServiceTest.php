<?php

namespace Modules\Udemy\Tests\Unit\Services;

use Exception;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Modules\Udemy\Events\BeforeProcessCompleteCurriculumItemEvent;
use Modules\Udemy\Events\Courses\SyncMyCourseCompletedEvent;
use Modules\Udemy\Events\UserHaveNoCoursesSubscribedEvent;
use Modules\Udemy\Jobs\SyncMyCoursesJob;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\UdemyService;
use Modules\Udemy\Tests\TestCase;

class UdemyServiceTest extends TestCase
{
    public function testSyncMyCourse()
    {
        Event::fake(
            [
                SyncMyCourseCompletedEvent::class,
                UserHaveNoCoursesSubscribedEvent::class,
            ]
        );

        $service = app(UdemyService::class);
        $userToken = UserToken::factory()->create();

        $service->syncMyCourse(
            $userToken,
            [
                'page' => 1,
                'page_size' => 40,
            ]
        );

        Event::assertDispatchedTimes(SyncMyCourseCompletedEvent::class, 40);

        $service->syncMyCourse(
            $userToken,
            [
                'error' => 403,
            ]
        );
        Event::assertDispatched(UserHaveNoCoursesSubscribedEvent::class);
    }

    public function testSyncMyCourses()
    {
        Queue::fake([
            SyncMyCoursesJob::class,
        ]);

        app(UdemyService::class)->syncMyCourses(UserToken::factory()->create());
        Queue::assertPushed(SyncMyCoursesJob::class);
    }

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
