<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Services\UdemyService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class ProcessMyCourses extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'udemy:process-my-courses {token}';

    /**
     * The console command description.
     */
    protected $description = 'Process my courses.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        app(UdemyService::class)->processNotCompletedCourses($this->argument('token'));
    }

}
