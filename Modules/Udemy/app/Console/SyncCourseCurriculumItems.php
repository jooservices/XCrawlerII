<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Jobs\SyncCourseCurriculumItemsJob;
use Modules\Udemy\Models\UserToken;

class SyncCourseCurriculumItems extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'udemy:sync-course-curriculum-items';

    /**
     * The console command description.
     */
    protected $description = 'Study specific course.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $token = $this->choice(
            'Enter your Udemy token',
            UserToken::all()->map(function ($user) {
                return $user->token;
            })->toArray()
        );

        /**
         * @var UserToken $userToken
         */
        $userToken = UserToken::where('token', $token)->first();

        if (!$userToken) {
            $this->error('Invalid token');
            return;
        }

        $courses = $userToken->notCompletedCourses();

        $this->table([
            'ID',
            'Course',
            'Completion Ratio',
        ], $courses->map(function ($course) {
            return [
                $course->id,
                $course->title,
                $course->pivot->completion_ratio,
            ];
        }));

        $courseId = $this->ask('Choose a course to study');
        $this->info('Syncing curriculum items...');

        SyncCourseCurriculumItemsJob::dispatch($userToken, $courses->find($courseId));
    }
}
