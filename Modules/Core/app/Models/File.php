<?php

namespace Modules\Core\Models;

use Dyrynda\Database\Support\GeneratesUuid;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use GeneratesUuid;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'server_id',
        'path',
        'filename',

        'mime_type',
        'encoder',
        'extension',
        'size',

        'frame_rate',

        'width',
        'height',

        'metadata',
    ];

    protected $casts = [
        'server_id' => 'integer',
        'path' => 'string',
        'filename' => 'string',

        'mime_type' => 'string',
        'encoder' => 'string',
        'extension' => 'string',
        'size' => 'string',

        'frame_rate' => 'float',

        'width' => 'integer',
        'height' => 'integer',

        'metadata' => 'array',
    ];

    // protected static function newFactory(): FileFactory
    // {
    //     // return FileFactory::new();
    // }
}
