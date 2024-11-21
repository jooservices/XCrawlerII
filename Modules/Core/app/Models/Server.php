<?php

namespace Modules\Core\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class Server extends Model
{
    use GeneratesUuid;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'ip',
    ];

    protected $casts = [
        'name' => 'string',
        'ip' => 'string',
    ];
}
