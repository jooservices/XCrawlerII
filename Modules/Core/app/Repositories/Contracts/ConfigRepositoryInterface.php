<?php

namespace Modules\Core\Repositories\Contracts;

use Modules\Core\Models\MongoDb\Config;

interface ConfigRepositoryInterface
{
    /**
     * Get a config value by group and key.
     */
    public function get(string $group, string $key): ?Config;

    /**
     * Set a config value by group and key, creating if it does not exist.
     */
    public function updateOrCreate(string $group, string $key, mixed $value, ?string $description = null): Config;
}
