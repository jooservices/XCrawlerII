<?php

namespace Modules\Core\Repositories;

use Modules\Core\Contracts\ConfigRepositoryInterface;
use Modules\Core\Models\MongoDb\Config;

class ConfigRepository implements ConfigRepositoryInterface
{
    /**
     * Get a config value by group and key.
     */
    public function get(string $group, string $key): ?Config
    {
        return Config::where('group', $group)
            ->where('key', $key)
            ->first();
    }

    /**
     * Set a config value by group and key, creating if it does not exist.
     */
    public function updateOrCreate(string $group, string $key, mixed $value, ?string $description = null): Config
    {
        $data = [
            'value' => $value,
        ];

        if ($description !== null) {
            $data['description'] = $description;
        }

        return Config::updateOrCreate(
            ['group' => $group, 'key' => $key],
            $data
        );
    }
}
