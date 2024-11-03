<?php

namespace Modules\Udemy\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Udemy\Database\Factories\UdemyMyCourseFactory;

/**
 * @property int $udemy_id
 * @property int $user_token_id
 */
class UdemyMyCourse extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'udemy_id',
        'user_token_id'
    ];

    // protected static function newFactory(): UdemyMyCourseFactory
    // {
    //     // return UdemyMyCourseFactory::new();
    // }
}
