<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Console\Traits\THasToken;
use Modules\Udemy\Models\UdemyCourse;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Repositories\UdemyCourseRepository;
use Modules\Udemy\Repositories\UserTokenRepository;
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
        $userToken = app(UserTokenRepository::class)
            ->createWithToken($this->getToken());

        app(UdemyService::class)->syncCurriculumItems($userToken, $course);
    }
}
