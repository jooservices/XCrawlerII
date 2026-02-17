<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;

class MissavSchedule extends Model
{
    protected $table = 'missav_schedules';

    protected $fillable = [
        'item_id',
        'code',
        'title',
        'url',
        'status',
        'attempts',
        'last_error',
        'scheduled_at',
        'processed_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
    ];
}
