<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\StudyService;

class StudyCourse extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'udemy:study-course';

    /**
     * The console command description.
     */
    protected $description = 'Sync courses.';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        /**
         * @var UserToken $userToken
         */
        $userToken = UserToken::where(
            'token',
            $this->ask('Enter your Udemy token')
        )->first();

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
        $course = $courses->find($courseId);

        $this->table(
            [
                'ID',
                'Title',
                'Type',
            ],
            $course->items->map(function (CurriculumItem $item) {
                return [
                    $item->id,
                    $item->title,
                    $item->detectType(),
                ];
            })
        );

        $this->output->info('Total items: ' . $course->items->count());

        $this->output->info('Studying course: ' . $course->title . '...');

        app(StudyService::class)->study($userToken, $course);
    }
}
