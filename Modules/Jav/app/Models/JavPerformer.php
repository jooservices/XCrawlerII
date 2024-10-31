<?php

namespace Modules\Jav\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Jav\Database\Factories\JavPerformerFactory;

class JavPerformer extends Model
{
    use GeneratesUuid;
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'uuid',
        'name'
    ];

    protected $casts = [
        'uuid' => 'string',
        'name' => 'string',
    ];

    // protected static function newFactory(): JavPerformerFactory
    // {
    //     // return JavPerformerFactory::new();
    // }
}
