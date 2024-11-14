<?php

namespace Modules\Jav\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

// use Modules\Jav\Database\Factories\JavMoviesFactory;

class JavMovie extends Model
{
    use GeneratesUuid;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'cover',
        'title',
        'dvd_id',
        'size',
    ];

    public function performers()
    {
        return $this->morphedByMany(
            JavPerformer::class,
            'jav_movieable',
            'jav_movieables',
            'movie_id'
        )->withTimestamps();
    }

    public function genres()
    {
        return $this->morphedByMany(
            JavGenre::class,
            'jav_movieable',
            'jav_movieables',
            'movie_id'
        )->withTimestamps();
    }

    // protected static function newFactory(): JavMoviesFactory
    // {
    //     // return JavMoviesFactory::new();
    // }
}
