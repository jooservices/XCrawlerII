<?php

namespace Modules\Jav\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Jav\Database\Factories\JavGenreFactory;

// use Modules\Jav\Database\Factories\JavGenreFactory;

class JavGenre extends Model
{
    use HasFactory;
    use GeneratesUuid;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'name',
    ];

    protected static function newFactory(): JavGenreFactory
    {
        return JavGenreFactory::new();
    }
}
