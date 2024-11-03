<?php

namespace Modules\Udemy\Tests\Unit\Jobs;

use Illuminate\Support\Facades\Event;
use Modules\Udemy\Events\CourseReadyForStudyEvent;
use Modules\Udemy\Jobs\SyncCurriculumItemsJob;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Tests\TestCase;

class SyncCurriculumItemsJobTest extends TestCase
{
    public function testSuccess()
    {
        $userToken = UserToken::factory()->create();
        $course = UdemyCourse::factory()->create([
            'id' => 59583,
        ]);

        $userToken->courses()->syncWithoutDetaching(
            [
                $course->id => [
                    'completion_ratio' => $this->faker->numberBetween(1, 99),
                    'enrollment_time' => $this->faker->dateTime(),
                ],
            ]
        );

        Event::fake([
            CourseReadyForStudyEvent::class,
        ]);
        SyncCurriculumItemsJob::dispatch($userToken, $course);
        $this->assertCount(54, $course->items);

        Event::assertDispatched(CourseReadyForStudyEvent::class);
    }
}
