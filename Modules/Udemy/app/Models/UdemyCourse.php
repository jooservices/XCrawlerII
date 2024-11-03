<?php

namespace Modules\Udemy\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Modules\Udemy\Database\Factories\UdemyCourseFactory;

/**
 * @property int $id
 * @property string $title
 * @property CurriculumItem|Collection $items
 */
class UdemyCourse extends Model
{
    use GeneratesUuid;
    use HasFactory;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'is_course_available_in_org',
        'is_practice_test_course',
        'is_private',
        'is_published',
        'published_title',
        'title',
        'url',
        'class',
    ];

    protected $casts = [
        'id' => 'integer',
        'is_course_available_in_org' => 'boolean',
        'is_practice_test_course' => 'boolean',
        'is_private' => 'boolean',
        'is_published' => 'boolean',
        'published_title' => 'string',
        'title' => 'string',
        'url' => 'string',
        'class' => 'string',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(CurriculumItem::class, 'course_id');
    }

    protected static function newFactory(): UdemyCourseFactory
    {
        return UdemyCourseFactory::new();
    }
}
