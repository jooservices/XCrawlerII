<?php

namespace Modules\Udemy\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Event;
use Modules\Udemy\Database\Factories\UdemyCourseFactory;
use Modules\Udemy\Events\CurriculumItemCreatedEvent;
use Modules\Udemy\Events\CurriculumItems\SyncCurriculumItemsCompletedEvent;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;

class SyncCurriculumItemsJobTest extends TestCase
{
    public function testSuccess()
    {
        $userToken = UserToken::factory()->create();
        /**
         * @var UdemyCourse $course
         */
        $course = UdemyCourse::factory()->create(['id' => UdemyCourseFactory::COURSE_ID]);

        $userToken->courses()->syncWithoutDetaching(
            [
                $course->id => [
                    'completion_ratio' => $this->faker->numberBetween(1, 99),
                    'enrollment_time' => $this->faker->dateTime(),
                ],
            ]
        );

        Event::fake([
            CurriculumItemCreatedEvent::class,
            SyncCurriculumItemsCompletedEvent::class,
        ]);

        SyncCurriculumItemsJob::dispatch($userToken, $course);
        $this->assertCount(54, $course->items);

        Event::assertDispatched(CurriculumItemCreatedEvent::class);
        Event::assertDispatched(SyncCurriculumItemsCompletedEvent::class);
    }
}
