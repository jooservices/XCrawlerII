<?php

namespace Modules\Udemy\Tests\Unit\Events;

use Illuminate\Support\Facades\Event;
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
        $udemyCourse = UdemyCourse::factory()->create([
            'id' => 59583,
        ]);

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

        Event::assertNotDispatched(CourseReadyForStudyEvent::class);
    }

    public function testEventWhenCourseIsReadyForStudy(): void
    {
        Event::fake([
            CourseReadyForStudyEvent::class,
        ]);

        $userToken = UserToken::factory()->create();
        $udemyCourse = UdemyCourse::factory()->create([
            'id' => 59583,
        ]);

        /**
         * Attach without pivot value
         */
        $userToken->courses()->syncWithoutDetaching([
            $udemyCourse->id => [
                'completion_ratio' => $this->faker->numberBetween(1,99),
            ],
        ]);

        $entities = app(UdemySdk::class)->courses()->subscriberCurriculumItems(
            $userToken,
            $udemyCourse->id
        );

        CurriculumItem::factory()->count(54)
            ->create(['course_id' => $udemyCourse->id,]);

        CurriculumItemCreatedEvent::dispatch($userToken, $entities, $udemyCourse->items->first());

        Event::assertDispatched(CourseReadyForStudyEvent::class);
    }
}
