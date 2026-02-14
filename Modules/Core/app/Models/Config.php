<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Config extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\ConfigFactory::new();
    }

    protected $fillable = [
        'group',
        'key',
        'value',
        'description',
    ];
}
