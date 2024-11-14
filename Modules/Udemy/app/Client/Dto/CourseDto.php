<?php

namespace Modules\Udemy\Client\Dto;

use Carbon\Carbon;
use Modules\Core\Dto\AbstractBaseDto;

/**
 * @property string $url
 * @property int $completion_ratio
 * @property string $enrollment_time
 */
class CourseDto extends AbstractBaseDto
{
    public const array FIELDS = [
        'archive_time',
        'buyable_object_type',
        'completion_ratio',
        'enrollment_time',
        'favorite_time',
        'features',
        'image_240x135',
        'image_480x270',
        'is_practice_test_course',
        'is_private',
        'is_published',
        'last_accessed_time',
        'num_collections',
        'published_title',
        'title',
        'tracking_id',
        'url',
        'visible_instructors',
        'is_course_available_in_org',
    ];

    public function getFields(): array
    {
        return self::FIELDS;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getCompletionRatio(): int
    {
        return $this->completion_ratio;
    }

    public function getEnrollmentTime(): ?Carbon
    {
        return $this->enrollment_time ? Carbon::parse($this->enrollment_time) : null;
    }

    public function isCompleted(): bool
    {
        return $this->getCompletionRatio() === 100;
    }
}
