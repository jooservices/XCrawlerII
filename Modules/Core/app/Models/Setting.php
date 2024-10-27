<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Modules\Core\Database\Factories\SettingFactory;
use MongoDB\Laravel\Eloquent\Model;

/**
 * @property mixed $value
 */
class Setting extends Model
{
    use HasFactory;

    protected string $collection = 'settings';
    protected $connection = 'mongodb';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'group',
        'key',
        'value'
    ];

    protected $casts = [
        'group' => 'string',
        'key' => 'string',
        'value' => 'string'
    ];

    protected static function newFactory(): SettingFactory
    {
        return SettingFactory::new();
    }
}
