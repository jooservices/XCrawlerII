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

        if ($config) {
            return $config->value;
        }

        $envKey = $this->buildEnvKey($group, $key);
        $envValue = $this->readEnv($envKey);

        return $envValue !== null ? $envValue : $default;
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

    private function buildEnvKey(string $group, string $key): string
    {
        $groupSegment = $this->normalizeEnvSegment($group);
        $keySegment = $this->normalizeEnvSegment($key);

        return trim($groupSegment . '_' . $keySegment, '_');
    }

    private function normalizeEnvSegment(string $value): string
    {
        $normalized = strtoupper($value);
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }

    private function readEnv(string $key): mixed
    {
        $value = env($key);

        if ($value === null) {
            $value = getenv($key);
        }

        if ($value === false) {
            return null;
        }

        return $value;
    }
}
