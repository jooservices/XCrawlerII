<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\UdemyService;

class CompleteMyCourse extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'udemy:complete-my-course {token} {course_id}';

    /**
     * The console command description.
     */
    protected $description = 'Command description.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $course = UdemyCourse::updateOrCreate([
            'id' => $this->argument('course_id'),
            'class' => 'course',
        ]);

        $userToken = UserToken::updateOrCreate(['token' => $this->argument('token')]);
        $userToken->courses()->syncWithoutDetaching([
            $course->id => [
                'completion_ratio' => 1,
            ],
        ]);

        app(UdemyService::class)->syncCurriculumItems($userToken, $course);
    }
}
