<?php

namespace Modules\Core\Models\MongoDb;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Models\MongoDb;

class Config extends MongoDb
{
    use HasFactory;

    public const string COLLECTION = 'configs';

    protected $table = self::COLLECTION;

    protected static function newFactory()
    {
        return \Modules\Core\Database\Factories\ConfigFactory::new();
    }

    protected $fillable = [
        'group',
        'key',
        'value',
        'description',
    ];
}
