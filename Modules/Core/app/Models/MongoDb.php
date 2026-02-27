<?php

declare(strict_types=1);

namespace Modules\Core\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * @property array<string, mixed> $attributes
 */
abstract class MongoDb extends Model
{
    protected $connection = 'mongodb';
}
