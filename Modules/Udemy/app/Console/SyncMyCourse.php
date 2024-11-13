<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Console\Traits\THasToken;
use Modules\Udemy\Services\UdemyService;

class SyncMyCourse extends Command
{
    use THasToken;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'udemy:sync-my-courses {token}';

    /**
     * The console command description.
     */
    protected $description = 'Sync my courses.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->output->title('Sync my courses');
        $this->output->text('Processing courses for first page...');

        /**
         * Sync courses as sync queue
         */
        $coursesDto = app(UdemyService::class)
            ->syncMyCourses($this->getToken());

        $this->output->info('Courses: ' . $coursesDto->getCount());
        $this->output->info('Pages: ' . $coursesDto->pages());

        $this->output->success('Completed');
    }
}
