<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Console\Traits\THasToken;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\StudyService;
use RuntimeException;

final class StudyCourse extends Command
{
    use THasToken;

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
        $userToken = $this->getToken();

        $courses = $userToken->notCompletedCourses();

        $this->table([
            'ID',
            'Course',
            'Completion Ratio',
            'URL',
        ], $courses->map(function ($course) {
            return [
                $course->id,
                $course->title,
                $course->pivot->completion_ratio,
                $course->getUrl(),
            ];
        }));

        $courseId = $this->ask('Choose a course to study');
        $course = $courses->find($courseId);

        if (!$course) {
            throw new RuntimeException('Course not found');
        }

        $this->info('Course URL: ' . config('udemy.client.base_uri') . $course->url);

        $this->info('Getting complete course details...');
        $ids = app(UdemySdk::class)
            ->setToken($userToken)
            ->me()->progress($courseId)->getCompletedIds();

        $this->table(
            [
                'Index',
                'ID',
                'Title',
                'Type',
                'Completed',
            ],
            $course->items->map(function (CurriculumItem $item, $index) use ($ids) {
                return [
                    $index,
                    $item->id,
                    $item->title,
                    $item->detectType(),
                    in_array($item->id, $ids, true) ? 'Yes' : 'No',
                ];
            })
        );

        $choice = $this->choice('Ready to study?', ['Yes', 'No'], 'Yes');

        if ($choice === 'No') {
            return;
        }

        $this->output->info('Studying course: ' . $course->title . '...');

        app(StudyService::class)->study($userToken, $course);
    }
}
