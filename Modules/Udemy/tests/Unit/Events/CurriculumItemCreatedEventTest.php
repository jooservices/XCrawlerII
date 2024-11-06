<?php

namespace Modules\Udemy\Tests\Unit\Events;

use Illuminate\Support\Facades\Event;
use Modules\Udemy\Database\Factories\UdemyCourseFactory;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Events\CurriculumItemCreatedEvent;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\Client\UdemySdk;
use Modules\Udemy\Tests\TestCase;

class CurriculumItemCreatedEventTest extends TestCase
{
    public function testEventWhenCourseIsNotReadyForStudy(): void
    {
        Event::fake([
            CourseReadyForStudyEvent::class,
        ]);

        $userToken = UserToken::factory()->create();
        $udemyCourse = UdemyCourse::factory()->create(['id' => UdemyCourseFactory::COURSE_ID,]);

        /**
         * Attach without pivot value
         */
        $userToken->courses()->attach($udemyCourse->id);

        $entities = app(UdemySdk::class)->courses()->subscriberCurriculumItems(
            $userToken,
            $udemyCourse->id
        );

        $item = $entities->getResults()->first();

        $model = $udemyCourse->items()->updateOrCreate(
            [
                'id' => $item->getId(),
            ],
            $item->toArray()
        );

        CurriculumItemCreatedEvent::dispatch($userToken, $entities, $model);

        /**
         * Item is created but it's not enough for completed
         */
        Event::assertNotDispatched(CourseReadyForStudyEvent::class);
    }
}
