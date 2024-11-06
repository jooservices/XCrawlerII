<?php

namespace Modules\Udemy\Tests\Unit\Events;

use Illuminate\Support\Facades\Event;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\SyncCurriculumItemsSyncCompletedEvent;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\Entities\CourseCurriculumItemsEntity;
use Modules\Udemy\Tests\TestCase;

class SyncCurriculumItemsSyncCompletedEventTest extends TestCase
{
    public function testWhenCourseReadyForStudy()
    {
        Event::fake([
            CourseReadyForStudyEvent::class,
        ]);

        $userToken = UserToken::factory()
            ->create();
        $course = UdemyCourse::factory()->create();
        $userToken->courses()->syncWithoutDetaching([
            $course->id => ['completion_ratio' => 99],
        ]);

        CurriculumItem::factory()->create();
        SyncCurriculumItemsSyncCompletedEvent::dispatch(
            $userToken,
            $userToken->courses->first(),
            new CourseCurriculumItemsEntity(json_decode(json_encode(['count' => 1])))
        );

        Event::assertDispatched(CourseReadyForStudyEvent::class);
    }

    public function testWhenCourseNotReadyForStudy()
    {
        Event::fake([
            CourseReadyForStudyEvent::class,
        ]);

        $userToken = UserToken::factory()
            ->create();
        $course = UdemyCourse::factory()->create();
        $userToken->courses()->syncWithoutDetaching([
            $course->id => ['completion_ratio' => 100],
        ]);

        CurriculumItem::factory()->create();
        SyncCurriculumItemsSyncCompletedEvent::dispatch(
            $userToken,
            $userToken->courses->first(),
            new CourseCurriculumItemsEntity(json_decode(json_encode(['count' => 1])))
        );

        Event::assertNotDispatched(CourseReadyForStudyEvent::class);
    }
}
