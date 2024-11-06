<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Console\Traits\THasToken;
use Modules\Udemy\Repositories\UdemyCourseRepository;
use Modules\Udemy\Services\UdemyService;

class CompleteMyCourse extends Command
{
    use THasToken;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'udemy:complete-my-course {token} {course_id}';

    /**
     * The console command description.
     */
    protected $description = 'Complete specific course.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $course = app(UdemyCourseRepository::class)
            ->createFromId($this->argument('course_id'));
        $userToken = $this->getToken();
        /**
         * Force complete course even it's completed
         */
        $userToken->courses()->syncWithoutDetaching([
            $course->id => [
                'completion_ratio' => 0,
            ],
        ]);

        app(UdemyService::class)->syncCurriculumItems($userToken, $course);
    }
}
