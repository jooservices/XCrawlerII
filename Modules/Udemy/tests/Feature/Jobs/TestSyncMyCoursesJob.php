<?php

namespace Modules\Udemy\Tests\Feature\Jobs;

use Illuminate\Support\Facades\Queue;
use Modules\Udemy\Jobs\SyncMyCoursesJob;
use Modules\Udemy\Services\UdemyService;
use Modules\Udemy\Tests\TestCase;

class TestSyncMyCoursesJob extends TestCase
{
    public function testSyncMyCourses(): void
    {
        Queue::fake([
            SyncMyCoursesJob::class,
        ]);

        app(UdemyService::class)->syncMyCourses($this->faker->uuid);

        Queue::assertPushed(SyncMyCoursesJob::class);
    }
}
