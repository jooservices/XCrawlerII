<?php

namespace Modules\Core\Services;

use Illuminate\Support\Str;
use Modules\Core\Models\Setting;

class SettingService
{
    public function get(string $group, string $key, ?string $default = null): string
    {
        $group = Str::slug($group, '_');
        $key = Str::slug($key, '_');

        $setting = Setting::where('group', $group)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    public function getInt(string $group, string $key, ?int $default = null): int
    {
        return (int) $this->get($group, $key, $default);
    }

    public function set(string $group, string $key, $value): self
    {
        $group = Str::slug($group, '_');
        $key = Str::slug($key, '_');

        Setting::updateOrCreate([
            'group' => $group,
            'key' => $key,
        ], ['value' => $value]);

        return $this;
    }
}
