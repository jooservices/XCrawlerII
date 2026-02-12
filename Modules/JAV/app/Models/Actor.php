<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Actor extends Model
{
    protected $table = 'actors';

    protected $fillable = ['name'];

    public function javs(): BelongsToMany
    {
        return $this->belongsToMany(Jav::class, 'jav_actor');
    }
}
