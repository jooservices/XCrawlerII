<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Services\UdemyService;

class SyncMyCourse extends Command
{
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
        app(UdemyService::class)->syncMyCourses($this->argument('token'));
    }
}
