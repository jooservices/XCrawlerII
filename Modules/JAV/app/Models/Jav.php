<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;

class Jav extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'jav';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'item_id',
        'code',
        'title',
        'url',
        'image',
        'date',
        'size',
        'description',
        'tags',
        'actresses',
        'download',
        'source',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'tags' => 'array',
        'actresses' => 'array',
        'date' => 'datetime',
        'size' => 'float',
    ];
}
