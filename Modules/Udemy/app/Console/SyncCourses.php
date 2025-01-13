<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Console\Traits\THasToken;
use Modules\Udemy\Services\UdemyService;

class SyncCourses extends Command
{
    use THasToken;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'udemy:sync-courses';

    /**
     * The console command description.
     */
    protected $description = 'Sync courses.';

    /**
     * Execute the console command.
     */
    final public function handle(UdemyService $service): int
    {
        $token = $this->getToken();
        $choice = $this->choice('Sync curriculum items', ['Yes', 'No'], 'Yes');
        $this->output->info('Syncing courses...');

        /**
         * Sync courses as sync queue
         */
        $coursesDto = $service->syncMyCourses(
            $token,
            [],
            $choice === 'Yes'
        );

        if (!$coursesDto) {
            $this->error('Can\'t sync courses');
        }

        $this->output->info('Courses: ' . $coursesDto->getCount());

        $this->output->success('Completed');

        return 0;
    }
}
