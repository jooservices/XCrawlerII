<?php

namespace Modules\Udemy\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Notifications\Notifiable;
use Modules\Udemy\Database\Factories\UserTokenFactory;

/**
 * @property string $token
 * @property string $name
 */
class UserToken extends Model
{
    use HasFactory;
    use Notifiable;
    use GeneratesUuid;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'token',
        'name',
    ];

    public function courses(): BelongsToMany
    {
        return $this->belongsToMany(
            UdemyCourse::class,
            'udemy_my_courses'
        )
            ->withTimestamps()
            ->withPivot([
                'completion_ratio',
                'enrollment_time',
            ]);
    }

    protected static function newFactory(): UserTokenFactory
    {
        return UserTokenFactory::new();
    }
}
