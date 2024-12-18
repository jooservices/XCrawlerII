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
    public function handle(): void
    {
        $token = $this->ask('Enter your Udemy token');

        /**
         * Sync courses as sync queue
         */
        $coursesDto = app(UdemyService::class)->syncMyCourses($this->getToken($token));

        $this->output->info('Courses: ' . $coursesDto->getCount());

        $this->output->success('Completed');
    }
}
