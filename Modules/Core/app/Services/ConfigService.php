<?php

namespace Modules\Core\Services;

use Modules\Core\Models\Config;

class ConfigService
{
    /**
     * Get a config value.
     */
    public function get(string $group, string $key, mixed $default = null): mixed
    {
        $config = Config::where('group', $group)
            ->where('key', $key)
            ->first();

        return $config ? $config->value : $default;
    }

    /**
     * Set a config value.
     */
    public function set(string $group, string $key, mixed $value, ?string $description = null): Config
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
