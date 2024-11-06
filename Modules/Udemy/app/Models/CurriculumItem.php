<?php

namespace Modules\Udemy\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Udemy\Database\Factories\CurriculumItemFactory;

/**
 * @property int $id
 * @property string $type
 * @property string $class
 * @property UdemyCourse $course
 * @property int $asset_time_estimation
 * @property string $asset_type
 */
class CurriculumItem extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'id',
        'course_id',
        'is_published',
        'title',
        'type',
        'class',
        'asset_id',
        'asset_type',
        'asset_filename',
        'asset_is_external',
        'asset_status',
        'asset_time_estimation',
        'asset_title',
        'asset_class',
    ];

    protected $casts = [
        'id' => 'integer',
        'course_id' => 'integer',
        'is_published' => 'boolean',
        'title' => 'string',
        'type' => 'string',
        'class' => 'string',
        'asset_id' => 'integer',
        'asset_type' => 'string',
        'asset_filename' => 'string',
        'asset_is_external' => 'boolean',
        'asset_status' => 'boolean',
        'asset_time_estimation' => 'integer',
        'asset_title' => 'string',
        'asset_class' => 'string',
    ];

    protected static function newFactory(): CurriculumItemFactory
    {
        return CurriculumItemFactory::new();
    }

    public function course()
    {
        return $this->belongsTo(UdemyCourse::class);
    }
}
