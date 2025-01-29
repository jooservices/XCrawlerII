<?php

namespace Modules\Udemy\Console;

use Illuminate\Console\Command;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Support\Collection;
use JsonException;
use Modules\Udemy\Client\UdemySdk;
use Modules\Udemy\Console\Traits\THasToken;
use Modules\Udemy\Models\CurriculumItem;
use Modules\Udemy\Models\UserToken;
use Modules\Udemy\Services\StudyService;
use RuntimeException;
use Throwable;

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
            'Curriculums',
            'Curriculums time',
            'Completion Ratio',
            'URL',
        ], $courses->map(function ($course) {
            return [
                $course->id,
                $course->title,
                $course->items->count(),
                $course->items->sum('asset_time_estimation') / 60 / 60,
                $course->pivot->completion_ratio,
                $course->getUrl(),
            ];
        }));

        do {
            $this->study($userToken, $courses);
        } while ($this->choice('Study another course', ['Yes', 'No'], 'Yes') === 'Yes');
    }

    /**
     * @throws BindingResolutionException
     * @throws Throwable
     * @throws JsonException
     */
    private function study(UserToken $userToken, Collection $courses): void
    {
        $courseId = $this->ask('Choose a course to study');
        $course = $courses->find($courseId);

        if (!$course) {
            throw new RuntimeException('Course not found');
        }

        $this->info('Course URL: ' . config('udemy.client.base_uri') . $course->url);

        $this->info('Getting complete course details...');
        $ids = app(UdemySdk::class)
            ->setToken($userToken)
            ->getCompletedIds($courseId);

        $this->table(
            [
                'Index',
                'ID',
                'Title',
                'Type',
                'Length',
                'Completed',
            ],
            $course->items()
                ->where('class', '<>', 'chapter')
                ->where('asset_type', '<>', 'E-Book')
                ->get()
                ->map(function (CurriculumItem $item, $index) use ($ids) {
                    return [
                        $index,
                        $item->id,
                        $item->title,
                        $item->detectType(),
                        $item->asset_time_estimation / 60 / 60,
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
