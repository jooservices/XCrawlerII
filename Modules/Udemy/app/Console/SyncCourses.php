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
    final public function handle(UdemyService $service): void
    {
        /**
         * Sync courses as sync queue
         */
        $coursesDto = $service->syncMyCourses($this->getToken());

        if ($coursesDto === null) {
            $this->output->error('Failed to sync courses');

            return;
        }

        $this->output->info('Courses: ' . $coursesDto->getCount());

        $this->output->success('Completed');
    }
}
