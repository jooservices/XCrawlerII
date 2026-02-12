<?php

namespace Modules\JAV\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    protected $table = 'tags';

    protected $fillable = ['name'];

    public function javs(): BelongsToMany
    {
        return $this->belongsToMany(Jav::class, 'jav_tag');
    }
}
