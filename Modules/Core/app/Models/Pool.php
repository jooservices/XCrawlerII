<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Core\Database\Factories\PoolFactory;

class Pool extends Model
{
    use HasFactory;

    protected $table = 'pools';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'server_ip',
        'server_name',
        'server_wan_ip',
        'name',
        'description',
    ];

    protected static function newFactory(): PoolFactory
    {
        return PoolFactory::new();
    }

    public function queues(): HasMany
    {
        return $this->hasMany(Queue::class);
    }
}
