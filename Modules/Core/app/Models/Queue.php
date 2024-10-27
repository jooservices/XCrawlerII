<?php

namespace Modules\Core\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Core\Database\Factories\QueueFactory;

class Queue extends Model
{
    use HasFactory;

    protected $table = 'queues';

    public const string STATE_CODE_INIT = 'INIT';
    public const string STATE_CODE_STARTED = 'STARTED';
    public const string STATE_CODE_COMPLETED = 'COMPLETED';

    public const string STATE_CODE_FAILED = 'FAILED';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'pool_id',
        'job_class',
        'state_code',
        'executed_at',
    ];

    protected static function newFactory(): QueueFactory
    {
        return QueueFactory::new();
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(Pool::class);
    }
}
